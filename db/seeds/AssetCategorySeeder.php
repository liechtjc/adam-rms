<?php


use Phinx\Seed\AbstractSeed;

class AssetCategorySeeder extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     */
    public function run(): void
    {

        $categoryGroupData = [
            [
                "assetCategoriesGroups_id" => 1,
                "assetCategoriesGroups_name" => "Camera",
                "assetCategoriesGroups_fontAwesome" => "fas fa-video",
                "assetCategoriesGroups_order" => 1,
                "instances_id" => null,
                "assetCategoriesGroups_deleted" => 0
            ],
            [
                "assetCategoriesGroups_id" => 2,
                "assetCategoriesGroups_name" => "Camera accessories",
                "assetCategoriesGroups_fontAwesome" => "fas fa-cog",
                "assetCategoriesGroups_order" => 2,
                "instances_id" => null,
                "assetCategoriesGroups_deleted" => 0
            ],
            [
                "assetCategoriesGroups_id" => 3,
                "assetCategoriesGroups_name" => "Lenses",
                "assetCategoriesGroups_fontAwesome" => "fas fa-circle",
                "assetCategoriesGroups_order" => 3,
                "instances_id" => null,
                "assetCategoriesGroups_deleted" => 0
            ],
            [
                "assetCategoriesGroups_id" => 4,
                "assetCategoriesGroups_name" => "Lighting",
                "assetCategoriesGroups_fontAwesome" => "far fa-lightbulb",
                "assetCategoriesGroups_order" => 4,
                "instances_id" => null,
                "assetCategoriesGroups_deleted" => 0
            ],
            [
                "assetCategoriesGroups_id" => 5,
                "assetCategoriesGroups_name" => "Audio",
                "assetCategoriesGroups_fontAwesome" => "fas fa-volume-up",
                "assetCategoriesGroups_order" => 5,
                "instances_id" => null,
                "assetCategoriesGroups_deleted" => 0
            ],
            [
                "assetCategoriesGroups_id" => 6,
                "assetCategoriesGroups_name" => "Grip + stabilisation",
                "assetCategoriesGroups_fontAwesome" => "fas fa-balance-scale-left",
                "assetCategoriesGroups_order" => 6,
                "instances_id" => null,
                "assetCategoriesGroups_deleted" => 0
            ],
            [
                "assetCategoriesGroups_id" => 7,
                "assetCategoriesGroups_name" => "Power",
                "assetCategoriesGroups_fontAwesome" => "fas fa-bolt",
                "assetCategoriesGroups_order" => 7,
                "instances_id" => null,
                "assetCategoriesGroups_deleted" => 0
            ],
            [
                "assetCategoriesGroups_id" => 8,
                "assetCategoriesGroups_name" => "Monitoring et wireless",
                "assetCategoriesGroups_fontAwesome" => "fas fa-tv",
                "assetCategoriesGroups_order" => 8,
                "instances_id" => null,
                "assetCategoriesGroups_deleted" => 0
            ]
        ];

        $categoryData = [
            // Camera (Group 1)
            [
                "assetCategories_id" => 1,
                "assetCategories_name" => "Full-Frame",
                "assetCategories_fontAwesome" => "fas fa-video",
                "assetCategories_rank" => 1,
                "assetCategoriesGroups_id" => 1,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 2,
                "assetCategories_name" => "Super35 / APS-C",
                "assetCategories_fontAwesome" => "fas fa-video",
                "assetCategories_rank" => 2,
                "assetCategoriesGroups_id" => 1,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 3,
                "assetCategories_name" => "MFT",
                "assetCategories_fontAwesome" => "fas fa-video",
                "assetCategories_rank" => 3,
                "assetCategoriesGroups_id" => 1,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 4,
                "assetCategories_name" => "Action cams + drone",
                "assetCategories_fontAwesome" => "fas fa-helicopter",
                "assetCategories_rank" => 4,
                "assetCategoriesGroups_id" => 1,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],

            // Camera accessories (Group 2)
            [
                "assetCategories_id" => 5,
                "assetCategories_name" => "Rig + handle",
                "assetCategories_fontAwesome" => "fas fa-grip-horizontal",
                "assetCategories_rank" => 5,
                "assetCategoriesGroups_id" => 2,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 6,
                "assetCategories_name" => "Mattebox",
                "assetCategories_fontAwesome" => "fas fa-square",
                "assetCategories_rank" => 6,
                "assetCategoriesGroups_id" => 2,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 7,
                "assetCategories_name" => "Focus",
                "assetCategories_fontAwesome" => "fas fa-bullseye",
                "assetCategories_rank" => 7,
                "assetCategoriesGroups_id" => 2,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 8,
                "assetCategories_name" => "Memory + reader",
                "assetCategories_fontAwesome" => "fas fa-sd-card",
                "assetCategories_rank" => 8,
                "assetCategoriesGroups_id" => 2,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],

            // Lenses (Group 3)
            [
                "assetCategories_id" => 9,
                "assetCategories_name" => "PL",
                "assetCategories_fontAwesome" => "fas fa-circle",
                "assetCategories_rank" => 9,
                "assetCategoriesGroups_id" => 3,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 10,
                "assetCategories_name" => "EF",
                "assetCategories_fontAwesome" => "fas fa-circle",
                "assetCategories_rank" => 10,
                "assetCategoriesGroups_id" => 3,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 11,
                "assetCategories_name" => "RF",
                "assetCategories_fontAwesome" => "fas fa-circle",
                "assetCategories_rank" => 11,
                "assetCategoriesGroups_id" => 3,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 12,
                "assetCategories_name" => "E",
                "assetCategories_fontAwesome" => "fas fa-circle",
                "assetCategories_rank" => 12,
                "assetCategoriesGroups_id" => 3,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 13,
                "assetCategories_name" => "Z",
                "assetCategories_fontAwesome" => "fas fa-circle",
                "assetCategories_rank" => 13,
                "assetCategoriesGroups_id" => 3,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 14,
                "assetCategories_name" => "MFT",
                "assetCategories_fontAwesome" => "fas fa-circle",
                "assetCategories_rank" => 14,
                "assetCategoriesGroups_id" => 3,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 15,
                "assetCategories_name" => "L",
                "assetCategories_fontAwesome" => "fas fa-circle",
                "assetCategories_rank" => 15,
                "assetCategoriesGroups_id" => 3,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 16,
                "assetCategories_name" => "Other mounts",
                "assetCategories_fontAwesome" => "fas fa-circle",
                "assetCategories_rank" => 16,
                "assetCategoriesGroups_id" => 3,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 17,
                "assetCategories_name" => "Adaptors",
                "assetCategories_fontAwesome" => "fas fa-link",
                "assetCategories_rank" => 17,
                "assetCategoriesGroups_id" => 3,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 18,
                "assetCategories_name" => "Filters",
                "assetCategories_fontAwesome" => "fas fa-filter",
                "assetCategories_rank" => 18,
                "assetCategoriesGroups_id" => 3,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],

            // Lighting (Group 4)
            [
                "assetCategories_id" => 19,
                "assetCategories_name" => "Spot / COB",
                "assetCategories_fontAwesome" => "fas fa-lightbulb",
                "assetCategories_rank" => 19,
                "assetCategoriesGroups_id" => 4,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 20,
                "assetCategories_name" => "Panels",
                "assetCategories_fontAwesome" => "fas fa-th-large",
                "assetCategories_rank" => 20,
                "assetCategoriesGroups_id" => 4,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 21,
                "assetCategories_name" => "Practicals",
                "assetCategories_fontAwesome" => "far fa-lightbulb",
                "assetCategories_rank" => 21,
                "assetCategoriesGroups_id" => 4,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 22,
                "assetCategories_name" => "Light modifiers",
                "assetCategories_fontAwesome" => "fas fa-adjust",
                "assetCategories_rank" => 22,
                "assetCategoriesGroups_id" => 4,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 23,
                "assetCategories_name" => "Frames + Flag",
                "assetCategories_fontAwesome" => "fas fa-border-all",
                "assetCategories_rank" => 23,
                "assetCategoriesGroups_id" => 4,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 24,
                "assetCategories_name" => "Accessories",
                "assetCategories_fontAwesome" => "fas fa-cog",
                "assetCategories_rank" => 24,
                "assetCategoriesGroups_id" => 4,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],

            // Audio (Group 5)
            [
                "assetCategories_id" => 25,
                "assetCategories_name" => "Wireless Microphones",
                "assetCategories_fontAwesome" => "fas fa-broadcast-tower",
                "assetCategories_rank" => 25,
                "assetCategoriesGroups_id" => 5,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 26,
                "assetCategories_name" => "Wired Microphones",
                "assetCategories_fontAwesome" => "fas fa-microphone",
                "assetCategories_rank" => 26,
                "assetCategoriesGroups_id" => 5,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 27,
                "assetCategories_name" => "Camera Top Microphones",
                "assetCategories_fontAwesome" => "fas fa-microphone-alt",
                "assetCategories_rank" => 27,
                "assetCategoriesGroups_id" => 5,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 28,
                "assetCategories_name" => "Audio Mixers & Recorders",
                "assetCategories_fontAwesome" => "fas fa-sliders-h",
                "assetCategories_rank" => 28,
                "assetCategoriesGroups_id" => 5,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 29,
                "assetCategories_name" => "Booms",
                "assetCategories_fontAwesome" => "fas fa-microphone-alt",
                "assetCategories_rank" => 29,
                "assetCategoriesGroups_id" => 5,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 30,
                "assetCategories_name" => "Cables",
                "assetCategories_fontAwesome" => "fas fa-network-wired",
                "assetCategories_rank" => 30,
                "assetCategoriesGroups_id" => 5,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 31,
                "assetCategories_name" => "Accessories",
                "assetCategories_fontAwesome" => "fas fa-headphones",
                "assetCategories_rank" => 31,
                "assetCategoriesGroups_id" => 5,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],

            // Grip + stabilisation (Group 6)
            [
                "assetCategories_id" => 32,
                "assetCategories_name" => "Tripods + video heads",
                "assetCategories_fontAwesome" => "fas fa-asterisk",
                "assetCategories_rank" => 32,
                "assetCategoriesGroups_id" => 6,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 33,
                "assetCategories_name" => "Gimbals",
                "assetCategories_fontAwesome" => "fas fa-sync",
                "assetCategories_rank" => 33,
                "assetCategoriesGroups_id" => 6,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 34,
                "assetCategories_name" => "Jib + sliders",
                "assetCategories_fontAwesome" => "fas fa-arrows-alt-h",
                "assetCategories_rank" => 34,
                "assetCategoriesGroups_id" => 6,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 35,
                "assetCategories_name" => "Car grip",
                "assetCategories_fontAwesome" => "fas fa-car",
                "assetCategories_rank" => 35,
                "assetCategoriesGroups_id" => 6,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 36,
                "assetCategories_name" => "Suction mount",
                "assetCategories_fontAwesome" => "fas fa-compress",
                "assetCategories_rank" => 36,
                "assetCategoriesGroups_id" => 6,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],

            // Power (Group 7)
            [
                "assetCategories_id" => 37,
                "assetCategories_name" => "Floor batteries AC 230v",
                "assetCategories_fontAwesome" => "fas fa-plug",
                "assetCategories_rank" => 37,
                "assetCategoriesGroups_id" => 7,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 38,
                "assetCategories_name" => "Floor batteries DC",
                "assetCategories_fontAwesome" => "fas fa-car-battery",
                "assetCategories_rank" => 38,
                "assetCategoriesGroups_id" => 7,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 39,
                "assetCategories_name" => "V-mount",
                "assetCategories_fontAwesome" => "fas fa-battery-full",
                "assetCategories_rank" => 39,
                "assetCategoriesGroups_id" => 7,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 40,
                "assetCategories_name" => "Chargers",
                "assetCategories_fontAwesome" => "fas fa-charging-station",
                "assetCategories_rank" => 40,
                "assetCategoriesGroups_id" => 7,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],

            // Monitoring et wireless (Group 8)
            [
                "assetCategories_id" => 41,
                "assetCategories_name" => "Monitors 5'' - 7''",
                "assetCategories_fontAwesome" => "fas fa-tablet-alt",
                "assetCategories_rank" => 41,
                "assetCategoriesGroups_id" => 8,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 42,
                "assetCategories_name" => "Monitors 17'' - 27''",
                "assetCategories_fontAwesome" => "fas fa-desktop",
                "assetCategories_rank" => 42,
                "assetCategoriesGroups_id" => 8,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 43,
                "assetCategories_name" => "Wireless system",
                "assetCategories_fontAwesome" => "fas fa-wifi",
                "assetCategories_rank" => 43,
                "assetCategoriesGroups_id" => 8,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ],
            [
                "assetCategories_id" => 44,
                "assetCategories_name" => "SDI cables",
                "assetCategories_fontAwesome" => "fas fa-network-wired",
                "assetCategories_rank" => 44,
                "assetCategoriesGroups_id" => 8,
                "instances_id" => null,
                "assetCategories_deleted" => 0
            ]
        ];

        $count = $this->fetchRow('SELECT COUNT(*) AS count FROM assetCategoriesGroups');
        if ($count['count'] > 0) {
            return;
        }
        $count = $this->fetchRow('SELECT COUNT(*) AS count FROM assetCategories');
        if ($count['count'] > 0) {
            return;
        }

        $table = $this->table('assetCategoriesGroups');
        $table->insert($categoryGroupData)
            ->saveData();
        $table = $this->table('assetCategories');
        $table->insert($categoryData)
            ->saveData();
    }
}
