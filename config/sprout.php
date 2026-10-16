<?php

return [
    // Multiplies how fast plants get thirsty/hungry. 1 = realistic, 30 = fast demo (set in .env).
    'decay_speed' => (float) env('SPROUT_DECAY_SPEED', 1),

    // Points lost per hour (before the multiplier). A full tank lasts about 8 h (water) / 12 h (food).
    'decay_per_hour' => ['hydration' => 12, 'hunger' => 8],

    // How much one watering / feeding refills.
    'care_amount' => 40,

    // XP rewards
    'xp' => ['water' => 10, 'feed' => 10, 'decorate' => 5, 'play' => 3],
    'play_cooldown_seconds' => 30,

    // Growth stages. A plant reaches a stage when its XP is at least "xp".
    'stages' => [
        ['name' => 'Seed', 'xp' => 0],
        ['name' => 'Sprout', 'xp' => 30],
        ['name' => 'Sapling', 'xp' => 100],
        ['name' => 'Young tree', 'xp' => 250],
        ['name' => 'Mature tree', 'xp' => 500],
    ],

    'decorations' => [
        'none' => 'None',
        'ribbon' => 'Ribbon',
        'flower' => 'Flower',
        'sunglasses' => 'Sunglasses',
        'star' => 'Star',
    ],

    // The plants a player can choose from.
    'plants' => [
        'narra' => ['label' => 'Narra', 'blurb' => 'The national tree. Proud and cheerful.', 'personality' => 'cheerful', 'leaf' => '#4C9A3F', 'bloom' => '#F2C230', 'pot' => '#C8794A'],
        'sampaguita' => ['label' => 'Sampaguita', 'blurb' => 'Small, shy and sweet-smelling.', 'personality' => 'shy', 'leaf' => '#5BAA4E', 'bloom' => '#FFFFFF', 'pot' => '#D9A066'],
        'mahogany' => ['label' => 'Mahogany', 'blurb' => 'Slow, sleepy and strong.', 'personality' => 'sleepy', 'leaf' => '#2F7A3E', 'bloom' => '#B5452B', 'pot' => '#9C6B45'],
        'acacia' => ['label' => 'Acacia', 'blurb' => 'Grumpy, but gives great shade.', 'personality' => 'grumpy', 'leaf' => '#7DB544', 'bloom' => '#E8D96A', 'pot' => '#B9794B'],
        'banaba' => ['label' => 'Banaba', 'blurb' => 'Loves attention and purple flowers.', 'personality' => 'cheerful', 'leaf' => '#3F8F55', 'bloom' => '#C45FB0', 'pot' => '#C8794A'],
        'santol' => ['label' => 'Santol', 'blurb' => 'Sleepy fruit tree with orange snacks.', 'personality' => 'sleepy', 'leaf' => '#5A9F3A', 'bloom' => '#E58A2B', 'pot' => '#A8693F'],
    ],
];
