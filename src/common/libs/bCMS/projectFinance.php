<?php

use Money\Money;
use Money\Currency;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;

class projectFinance
{
  public function durationMathsByDates($start, $end)
  {
    $start = strtotime(date("d F Y 00:00:00", strtotime($start)));
    $end = strtotime(date("d F Y 23:59:59", strtotime($end)));
    $diff = ceil(($end - $start) / 86400);
    if ($diff < 1) $diff = 1;
    return ["days" => $diff, "weeks" => 0, "calendarDays" => $diff];
  }
  public function durationMaths($projects_id)
  {
    global $DBLIB;
    $DBLIB->where("projects_id", $projects_id);
    $project = $DBLIB->getone("projects", ["projects_dates_finances_days", "projects_dates_finances_weeks", "projects_dates_deliver_start", "projects_dates_deliver_end"]);
    if (!$project) return false;

    if ($project['projects_dates_finances_days'] !== NULL and $project['projects_dates_finances_weeks'] !== NULL) {
      $rawDays = $this->durationMathsByDates($project['projects_dates_deliver_start'], $project['projects_dates_deliver_end']);
      return ["days" => $project['projects_dates_finances_days'], "weeks" => $project['projects_dates_finances_weeks'], "calendarDays" => $rawDays['days']];
    } else {
      return $this->durationMathsByDates($project['projects_dates_deliver_start'], $project['projects_dates_deliver_end']);
    }
  }
}
class projectFinanceCacher
{
  //This class assumes that the projectid has been validated as within the instance
  private $data, $projectid;
  private $changesMade = false;
  public function __construct($projectid)
  {
    global $AUTH;
    //Reset the data
    $this->projectid = $projectid;
    $this->data = [
      "projectsFinanceCache_equipmentSubTotal" => new Money(null, new Currency($AUTH->data['instance']['instances_config_currency'])),
      "projectsFinanceCache_equiptmentDiscounts" => new Money(null, new Currency($AUTH->data['instance']['instances_config_currency'])),
      "projectsFinanceCache_salesTotal" => new Money(null, new Currency($AUTH->data['instance']['instances_config_currency'])),
      "projectsFinanceCache_staffTotal" => new Money(null, new Currency($AUTH->data['instance']['instances_config_currency'])),
      "projectsFinanceCache_externalHiresTotal" => new Money(null, new Currency($AUTH->data['instance']['instances_config_currency'])),
      "projectsFinanceCache_paymentsReceived" => new Money(null, new Currency($AUTH->data['instance']['instances_config_currency'])),
      "projectsFinanceCache_value" => new Money(null, new Currency($AUTH->data['instance']['instances_config_currency'])),
      "projectsFinanceCache_mass" => 0.0
    ];
  }
  public function save()
  {
    //Process the changes at the end of the script
    global $DBLIB;
    if ($this->changesMade) {
      $dataToUpload = [];
      foreach ($this->data as $key => $value) {
        //Put it into a format for mysql
        if ($key != 'projectsFinanceCache_mass') $value = $value->getAmount();
        if ($value != 0) $dataToUpload[$key] = $DBLIB->inc($value);
      }
      $dataToUpload['projectsFinanceCache_timestampUpdated'] = date("Y-m-d H:i:s");
      $dataToUpload['projectsFinanceCache_equiptmentTotal'] = $DBLIB->inc($this->data["projectsFinanceCache_equipmentSubTotal"]->subtract($this->data['projectsFinanceCache_equiptmentDiscounts'])->getAmount());
      $dataToUpload['projectsFinanceCache_grandTotal'] = $DBLIB->inc((($this->data["projectsFinanceCache_equipmentSubTotal"]->subtract($this->data['projectsFinanceCache_equiptmentDiscounts']))->add($this->data['projectsFinanceCache_salesTotal'], $this->data['projectsFinanceCache_staffTotal'], $this->data["projectsFinanceCache_externalHiresTotal"])->subtract($this->data['projectsFinanceCache_paymentsReceived']))->getAmount());
      $DBLIB->where("projects_id", $this->projectid);
      $DBLIB->orderBy("projectsFinanceCache_timestamp", "DESC");
      return $DBLIB->update("projectsFinanceCache", $dataToUpload, 1); //Update the most recent cache datapoint
    } else return true;
  }
  public function adjust($key, $value, $subtract = false)
  {
    if ($key == 'projectsFinanceCache_mass' and ($value !== 0 or $value !== null)) {
      $this->changesMade = true;
      if ($subtract) $value = -1 * $value;
      $this->data[$key] += $value;
    } else {
      $this->changesMade = true;
      //It's a money object!
      if ($subtract) {
        $this->data[$key] = $this->data[$key]->subtract($value);
      } else {
        $this->data[$key] = $this->data[$key]->add($value);
      }
    }
  }
  public function adjustPayment($paymentType, $value, $subtract = false)
  {
    switch ($paymentType) {
      case 1:
        $key = 'projectsFinanceCache_paymentsReceived';
        break;
      case 2:
        $key = 'projectsFinanceCache_salesTotal';
        break;
      case 3:
        $key = 'projectsFinanceCache_externalHiresTotal';
        break;
      case 4:
        $key = 'projectsFinanceCache_staffTotal';
        break;
      default:
        return false;
    }
    return $this->adjust($key, $value, $subtract);
  }
}

function projectFinancials($project) {
    global $DBLIB,$AUTH,$bCMS;
    $projectFinanceHelper = new projectFinance();
    $return = [];

    //create a formatter for money
    $numberFormatter = new \NumberFormatter('en_GB', \NumberFormatter::CURRENCY);
    $moneyFormatter = new IntlMoneyFormatter($numberFormatter, new ISOCurrencies());

    $DBLIB->where("payments.payments_deleted", 0);
    $DBLIB->orderBy("payments.payments_date", "ASC");
    $DBLIB->where("payments.projects_id", $project['projects_id']);
    $payments = $DBLIB->get("payments");
    $return['payments'] = ["received" => ["ledger" => [], "total" => new Money(null, new Currency($AUTH->data['instance']['instances_config_currency']))], "sales" => ["ledger" => [], "total" => new Money(null, new Currency($AUTH->data['instance']['instances_config_currency']))], "subHire" => ["ledger" => [], "total" => new Money(null, new Currency($AUTH->data['instance']['instances_config_currency']))], "staff" => ["ledger" => [], "total" => new Money(null, new Currency($AUTH->data['instance']['instances_config_currency']))]];
    foreach ($payments as $payment) {
        $payment['files'] = $bCMS->s3List(14, $payment['payments_id']);
        $key = false;
        switch ($payment['payments_type']) {
            case 1:
                $key = "received";
                break;
            case 2:
                $key = 'sales';
                break;
            case 3:
                $key = 'subHire';
                break;
            case 4:
                $key = 'staff';
                break;
        }
        if ($key) {
            $payment['payments_amount'] = new Money($payment['payments_amount'], new Currency($AUTH->data['instance']['instances_config_currency']));
            $payment['payments_amountTotal'] = $payment['payments_amount']->multiply($payment['payments_quantity']);
            $return['payments'][$key]['total'] = $payment['payments_amountTotal']->add($return['payments'][$key]['total']);
            $return['payments'][$key]['ledger'][] = $payment;
        } else throw new Exception("Unknown payment type found");
    }

    //Assets
    $DBLIB->where("projects_id", $project['projects_id']);
    $DBLIB->where("assetsAssignments.assetsAssignments_deleted", 0);
    $DBLIB->join("assets", "assetsAssignments.assets_id=assets.assets_id", "LEFT");
    $DBLIB->join("assetTypes", "assets.assetTypes_id=assetTypes.assetTypes_id", "LEFT");
    $DBLIB->join("manufacturers", "manufacturers.manufacturers_id=assetTypes.manufacturers_id", "LEFT");
    $DBLIB->join("assetCategories", "assetTypes.assetCategories_id=assetCategories.assetCategories_id", "LEFT");
    $DBLIB->join("assetCategoriesGroups", "assetCategoriesGroups.assetCategoriesGroups_id=assetCategories.assetCategoriesGroups_id", "LEFT");
    $DBLIB->join("assetsAssignmentsStatus", "assetsAssignments.assetsAssignmentsStatus_id=assetsAssignmentsStatus.assetsAssignmentsStatus_id", "LEFT");
    $DBLIB->orderBy("assetCategories.assetCategories_rank", "ASC");
    $DBLIB->orderBy("assetTypes.assetTypes_id", "ASC");
    $DBLIB->orderBy("assets.assets_tag", "ASC");
    $DBLIB->where("assets.assets_deleted", 0);
    $assets = $DBLIB->get("assetsAssignments", null, ["assetCategories.assetCategories_rank","assetsAssignmentsStatus.assetsAssignmentsStatus_id", "assetsAssignmentsStatus.assetsAssignmentsStatus_order", "assetsAssignmentsStatus.assetsAssignmentsStatus_name","assetsAssignments.*", "manufacturers.manufacturers_name", "assetTypes.*", "assets.*", "assetCategories.assetCategories_name", "assetCategories.assetCategories_fontAwesome", "assetCategoriesGroups.assetCategoriesGroups_name", "assets.instances_id"]);

    $return['assetsAssigned'] = [];
    $return['assetsAssignedSUB'] = [];
    $return['mass'] = 0.0; //TODO evaluate whether using floats for mass is a good idea....
    $return['value'] = new Money(null, new Currency($AUTH->data['instance']['instances_config_currency']));
    $return['prices'] = ["subTotal" => new Money(null, new Currency($AUTH->data['instance']['instances_config_currency'])), "discounts" => new Money(null, new Currency($AUTH->data['instance']['instances_config_currency'])), "total" => new Money(null, new Currency($AUTH->data['instance']['instances_config_currency']))];

    $return['priceMaths'] = $projectFinanceHelper->durationMaths($project['projects_id']);

    // Reorder: inject linked children immediately after their parent asset.
    // Must run before the instance routing split below — cross-instance parent-child
    // pairs must stay together regardless of which bucket they end up in.
    $childrenByParent = [];
    foreach ($assets as $asset) {
        if ($asset['assetsAssignments_linkedTo'] !== null) {
            $childrenByParent[$asset['assetsAssignments_linkedTo']][] = $asset;
        }
    }
    $ordered = [];
    foreach ($assets as $asset) {
        if ($asset['assetsAssignments_linkedTo'] !== null) continue;
        $ordered[] = $asset;
        if (isset($childrenByParent[$asset['assetsAssignments_id']])) {
            foreach ($childrenByParent[$asset['assetsAssignments_id']] as $child) {
                $ordered[] = $child;
            }
        }
    }
    $assets = $ordered;

    foreach ($assets as $asset) {
        $asset['value'] = new Money(($asset['assets_value'] != null ? $asset['assets_value'] : $asset['assetTypes_value']), new Currency($AUTH->data['instance']['instances_config_currency']));
        if ($asset['assetsAssignments_linkedTo'] === null) {
            //Linked assets are purely descriptive - they don't contribute to project totals
            $return['mass'] += ($asset['assets_mass'] == null ? $asset['assetTypes_mass'] : $asset['assets_mass']);
            $return['value'] = $return['value']->add($asset['value']);
        }

        if ($asset['assetsAssignments_customPrice'] == null) {
            //The actual pricing calculator
            $asset['price'] = new Money(null, new Currency($AUTH->data['instance']['instances_config_currency']));
            $asset['price'] = $asset['price']->add((new Money(($asset['assets_dayRate'] !== null ? $asset['assets_dayRate'] : $asset['assetTypes_dayRate']), new Currency($AUTH->data['instance']['instances_config_currency'])))->multiply($return['priceMaths']['days']));
            $asset['price'] = $asset['price']->add((new Money(($asset['assets_weekRate'] !== null ? $asset['assets_weekRate'] : $asset['assetTypes_weekRate']), new Currency($AUTH->data['instance']['instances_config_currency'])))->multiply($return['priceMaths']['weeks']));
        } else $asset['price'] = new Money($asset['assetsAssignments_customPrice'],new Currency($AUTH->data['instance']['instances_config_currency']));

        if ($asset['assetsAssignments_discount'] > 0) $asset['discountPrice'] = $asset['price']->multiply(1 - ($asset['assetsAssignments_discount'] / 100));
        else $asset['discountPrice'] = $asset['price'];

        if ($asset['assetsAssignments_linkedTo'] === null) {
            //Linked assets are purely descriptive - they don't contribute to project totals
            $return['prices']['subTotal'] = $asset['price']->add($return['prices']['subTotal']);
            $return['prices']['discounts'] = $return['prices']['discounts']->add($asset['price']->subtract($asset['discountPrice']));
            $return['prices']['total'] = $return['prices']['total']->add($asset['discountPrice']);
        }

        //Formatted values for each asset
        $asset['formattedValue'] = $moneyFormatter->format($asset['value']);
        $asset['formattedPrice'] = $moneyFormatter->format($asset['price']);
        $asset['formattedDiscountPrice'] = $moneyFormatter->format($asset['discountPrice']);
        $asset['formattedMass'] = number_format(($asset['assets_mass'] == null ? $asset['assetTypes_mass'] : $asset['assets_mass']), 2, '.', '') . "kg";

        $asset['flagsblocks'] = assetFlagsAndBlocks($asset['assets_id']);

        $asset['assetTypes_definableFields_ARRAY'] = array_filter(explode(",", $asset['assetTypes_definableFields']));

        $asset['latestScan'] = assetLatestScan($asset['assets_id']);

        if ($asset['instances_id'] != $project['instances_id']) {
            if (!isset($return['assetsAssignedSUB'][$asset['instances_id']]['assets'])) $return['assetsAssignedSUB'][$asset['instances_id']]['assets'] = [];
            if (!isset($return['assetsAssignedSUB'][$asset['instances_id']]['assets'][$asset['assetTypes_id']])) $return['assetsAssignedSUB'][$asset['instances_id']]['assets'][$asset['assetTypes_id']]['assets'] = [];
            $return['assetsAssignedSUB'][$asset['instances_id']]['assets'][$asset['assetTypes_id']]['assets'][] = $asset;
        } else {
            if (!isset($return['assetsAssigned'][$asset['assetTypes_id']])) $return['assetsAssigned'][$asset['assetTypes_id']]['assets'] = [];
            $return['assetsAssigned'][$asset['assetTypes_id']]['assets'][] = $asset;
        }
    }
    foreach ($return['assetsAssigned'] as $key => $type) {
        if (!isset($return['assetsAssigned'][$key]['totals'])) $return['assetsAssigned'][$key]['totals'] = ["status" => null,"discountPrice"=>new Money(null, new Currency($AUTH->data['instance']['instances_config_currency'])),"price"=>new Money(null, new Currency($AUTH->data['instance']['instances_config_currency'])),"mass"=>0.0];
        foreach ($type['assets'] as $asset) {
            if ($return['assetsAssigned'][$key]['totals']['status'] == null) $return['assetsAssigned'][$key]['totals']['status'] = $asset['assetsAssignmentsStatus_name'];
            elseif ($return['assetsAssigned'][$key]['totals']['status'] != $asset['assetsAssignmentsStatus_name']) $return['assetsAssigned'][$key]['totals']['status'] = false; //They aren't all the same
            if ($asset['assetsAssignments_linkedTo'] === null) {
                //Linked assets are purely descriptive - they don't contribute to project totals
                $return['assetsAssigned'][$key]['totals']['discountPrice'] = $return['assetsAssigned'][$key]['totals']['discountPrice']->add($asset['discountPrice']);
                $return['assetsAssigned'][$key]['totals']['price'] = $return['assetsAssigned'][$key]['totals']['price']->add($asset['price']);
                $return['assetsAssigned'][$key]['totals']['mass'] += ($asset['assets_mass'] == null ? $asset['assetTypes_mass'] : $asset['assets_mass']);
            }
        }
        //formatted Totals
        $return['assetsAssigned'][$key]['totals']['formattedDiscountPrice'] = $moneyFormatter->format($return['assetsAssigned'][$key]['totals']['discountPrice']);
        $return['assetsAssigned'][$key]['totals']['formattedPrice'] = $moneyFormatter->format($return['assetsAssigned'][$key]['totals']['price']);
        $return['assetsAssigned'][$key]['totals']['formattedMass'] = number_format($return['assetsAssigned'][$key]['totals']['mass'], 2, '.', '') . "kg";
    }
    foreach ($return['assetsAssignedSUB'] as $instanceid => $instance) {
        if (!isset($return['assetsAssignedSUB'][$instanceid]['instance'])) {
            $DBLIB->where("instances_id",$instanceid);
            $return['assetsAssignedSUB'][$instanceid]['instance'] = $DBLIB->getone("instances",["instances_id","instances_name"]);
        }
        foreach ($return['assetsAssignedSUB'][$instanceid]['assets'] as $key => $type) {
            if (!isset($return['assetsAssignedSUB'][$instanceid]['assets'][$key]['totals'])) $return['assetsAssignedSUB'][$instanceid]['assets'][$key]['totals'] = ["status" => null,"discountPrice"=>new Money(null, new Currency($AUTH->data['instance']['instances_config_currency'])),"price"=>new Money(null, new Currency($AUTH->data['instance']['instances_config_currency'])),"mass"=>0.0];
            foreach ($type['assets'] as $asset) {
                if ($return['assetsAssignedSUB'][$instanceid]['assets'][$key]['totals']['status'] == null) $return['assetsAssignedSUB'][$instanceid]['assets'][$key]['totals']['status'] = $asset['assetsAssignmentsStatus_name'];
                elseif ($return['assetsAssignedSUB'][$instanceid]['assets'][$key]['totals']['status'] != $asset['assetsAssignmentsStatus_name']) $return['assetsAssignedSUB'][$instanceid]['assets'][$key]['totals']['status'] = false; //They aren't all the same
                if ($asset['assetsAssignments_linkedTo'] === null) {
                    //Linked assets are purely descriptive - they don't contribute to project totals
                    $return['assetsAssignedSUB'][$instanceid]['assets'][$key]['totals']['discountPrice'] = $return['assetsAssignedSUB'][$instanceid]['assets'][$key]['totals']['discountPrice']->add($asset['discountPrice']);
                    $return['assetsAssignedSUB'][$instanceid]['assets'][$key]['totals']['price'] = $return['assetsAssignedSUB'][$instanceid]['assets'][$key]['totals']['price']->add($asset['price']);
                    $return['assetsAssignedSUB'][$instanceid]['assets'][$key]['totals']['mass'] += ($asset['assets_mass'] == null ? $asset['assetTypes_mass'] : $asset['assets_mass']);
                }
            }
            //Formatted totals
            $return['assetsAssignedSUB'][$instanceid]['assets'][$key]['totals']['formattedDiscountPrice'] = $moneyFormatter->format($return['assetsAssignedSUB'][$instanceid]['assets'][$key]['totals']['discountPrice']);
            $return['assetsAssignedSUB'][$instanceid]['assets'][$key]['totals']['formattedPrice'] = $moneyFormatter->format($return['assetsAssignedSUB'][$instanceid]['assets'][$key]['totals']['price']);
            $return['assetsAssignedSUB'][$instanceid]['assets'][$key]['totals']['formattedMass'] = number_format($return['assetsAssignedSUB'][$instanceid]['assets'][$key]['totals']['mass'], 2, '.', '') . "kg";
        }
    }

    //Insurance - a percentage of the equipment subtotal (pre-discount), or a fixed amount if one is set
    $return['insurance'] = [
        "rate" => $project['projects_insurance_rate'],
        "amount" => $project['projects_insurance_amount'],
    ];
    $return['insurance']['total'] = ($project['projects_insurance_amount'] !== null && $project['projects_insurance_amount'] != 0)
        ? new Money($project['projects_insurance_amount'], new Currency($AUTH->data['instance']['instances_config_currency']))
        : $return['prices']['subTotal']->multiply($project['projects_insurance_rate'] / 100);

    $return['payments']['subTotal'] = $return['prices']['total']->add($return['payments']['sales']['total'],$return['payments']['subHire']['total'],$return['payments']['staff']['total'],$return['insurance']['total']);

    //VAT - applies to every project at the instance rate, unless the project is marked as an Export
    $return['vat'] = [
        "isExport" => (bool)$project['projects_vat_export'],
        "instanceRate" => $AUTH->data['instance']['instances_config_vatRate'],
        "rate" => $project['projects_vat_export'] ? 0.0 : $AUTH->data['instance']['instances_config_vatRate'],
    ];
    $return['vat']['total'] = $return['payments']['subTotal']->multiply($return['vat']['rate'] / 100);

    $return['payments']['grandTotal'] = $return['payments']['subTotal']->add($return['vat']['total']);
    $return['payments']['total'] = $return['payments']['grandTotal']->subtract($return['payments']['received']['total']);

    //add formatted values to everything
    $return['formattedValue'] = $moneyFormatter->format($return['value']);
    $return['formattedPrices'] = ["subTotal" => $moneyFormatter->format($return['prices']['subTotal']), "discounts" => $moneyFormatter->format($return['prices']['discounts']), "total" => $moneyFormatter->format($return['prices']['total'])];
    $return['formattedMass'] = number_format($return['mass'], 2, '.', '') . "kg";
    //format payments
    foreach ($return['payments'] as $key => $value) {
        if ($key == "subTotal") {
            $return['payments']['formattedSubTotal'] = $moneyFormatter->format($return['payments']["subTotal"]);
        } elseif ($key == "grandTotal") {
            $return['payments']['formattedGrandTotal'] = $moneyFormatter->format($return['payments']["grandTotal"]);
        } elseif ($key == "total"){
            $return['payments']['formattedTotal'] = $moneyFormatter->format($return['payments']["total"]);
        } else {
            $return['payments'][$key]['formattedTotal'] = $moneyFormatter->format($return['payments'][$key]['total']);
        }
    }
    $return['formattedInsurance'] = $moneyFormatter->format($return['insurance']['total']);
    $return['formattedVat'] = $moneyFormatter->format($return['vat']['total']);

    return $return;
}
