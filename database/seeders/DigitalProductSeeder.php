<?php

namespace Database\Seeders;

use App\Models\DigitalProduct;
use Illuminate\Database\Seeder;

class DigitalProductSeeder extends Seeder
{
    public function run(): void
    {
        // Example digital products: exam question banks
        $products = [
            [
                'title' => 'FPGEC Exam Questions 2024',
                'description' => 'Comprehensive question bank with 500+ practice questions for FPGEC pharmacy licensing exam. Topics include pharmacology, clinical pharmacy, and pharmacy practice management.',
                'price' => 49.99,
                'is_active' => true,
                'is_free' => false,
                'access_days' => 365,
            ],
            [
                'title' => 'Prometric Practice Exams',
                'description' => '5 complete mock exams simulating the actual Prometric licensing test. Full explanations for every answer with detailed references.',
                'price' => 79.99,
                'is_active' => true,
                'is_free' => false,
                'access_days' => 180,
            ],
            [
                'title' => 'Pharmacology Study Guide',
                'description' => 'Complete pharmacology reference with 300+ Q&A pairs covering all major drug classes and mechanisms. Perfect for exam preparation and clinical reference.',
                'price' => 39.99,
                'is_active' => true,
                'is_free' => false,
                'access_days' => null, // Lifetime
            ],
            [
                'title' => 'Clinical Pharmacy Cases',
                'description' => '100 real-world clinical pharmacy case studies with critical thinking questions and expert answers.',
                'price' => 59.99,
                'is_active' => true,
                'is_free' => false,
                'access_days' => 365,
            ],
        ];

        foreach ($products as $productData) {
            DigitalProduct::create($productData);
        }
    }
}
