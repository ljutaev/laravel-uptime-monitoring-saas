<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubscriptionPlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ==================== FREE PLAN ====================
        $freePlan = SubscriptionPlan::create([
            'name' => 'Free',
            'slug' => 'free',
            'description' => 'Perfect for trying out',
            'is_active' => true,
            'trial_period_days' => 0,
        ]);

        // Ціни для Free плану
        PlanPrice::create([
            'plan_id' => $freePlan->id,
            'billing_period' => 'lifetime',
            'price' => 0,
            'currency' => 'USD',
        ]);

        // Features для Free
        PlanFeature::create(['plan_id' => $freePlan->id, 'slug' => 'domains', 'name' => 'Domains', 'value' => '1', 'sort_order' => 1]);
        PlanFeature::create(['plan_id' => $freePlan->id, 'slug' => 'api_calls', 'name' => 'API Calls', 'value' => '100', 'sort_order' => 2]);
        PlanFeature::create(['plan_id' => $freePlan->id, 'slug' => 'ssl_monitoring', 'name' => 'SSL Monitoring', 'value' => 'false', 'sort_order' => 3]);
        PlanFeature::create(['plan_id' => $freePlan->id, 'slug' => 'email_alerts', 'name' => 'Email Alerts', 'value' => '1', 'sort_order' => 4]);

        // ==================== BUSINESS PLAN ====================
        $businessPlan = SubscriptionPlan::create([
            'name' => 'Business',
            'slug' => 'business',
            'description' => 'For growing businesses',
            'is_active' => true,
            'trial_period_days' => 14,
        ]);

        // Ціни для Business плану
        PlanPrice::create([
            'plan_id' => $businessPlan->id,
            'billing_period' => 'monthly',
            'price' => 9.99,
            'currency' => 'USD',
        ]);

        PlanPrice::create([
            'plan_id' => $businessPlan->id,
            'billing_period' => 'yearly',
            'price' => 99.99, // Замість 119.88 (9.99 * 12)
            'currency' => 'USD',
            'discount_percentage' => 16.67, // ~20 USD знижка
        ]);

        // Features для Business
        PlanFeature::create(['plan_id' => $businessPlan->id, 'slug' => 'domains', 'name' => 'Domains', 'value' => '10', 'sort_order' => 1]);
        PlanFeature::create(['plan_id' => $businessPlan->id, 'slug' => 'api_calls', 'name' => 'API Calls', 'value' => '10000', 'sort_order' => 2]);
        PlanFeature::create(['plan_id' => $businessPlan->id, 'slug' => 'ssl_monitoring', 'name' => 'SSL Monitoring', 'value' => 'true', 'sort_order' => 3]);
        PlanFeature::create(['plan_id' => $businessPlan->id, 'slug' => 'email_alerts', 'name' => 'Email Alerts', 'value' => '5', 'sort_order' => 4]);

        // ==================== ENTERPRISE PLAN ====================
        $enterprisePlan = SubscriptionPlan::create([
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'description' => 'For large organizations',
            'is_active' => true,
            'trial_period_days' => 30,
        ]);

        // Ціни для Enterprise плану
        PlanPrice::create([
            'plan_id' => $enterprisePlan->id,
            'billing_period' => 'monthly',
            'price' => 99.99,
            'currency' => 'USD',
        ]);

        PlanPrice::create([
            'plan_id' => $enterprisePlan->id,
            'billing_period' => 'yearly',
            'price' => 999.99, // Замість 1199.88
            'currency' => 'USD',
            'discount_percentage' => 16.67, // ~200 USD знижка
        ]);

        // Features для Enterprise
        PlanFeature::create(['plan_id' => $enterprisePlan->id, 'slug' => 'domains', 'name' => 'Domains', 'value' => '25', 'sort_order' => 1]);
        PlanFeature::create(['plan_id' => $enterprisePlan->id, 'slug' => 'api_calls', 'name' => 'API Calls', 'value' => 'unlimited', 'sort_order' => 2]);
        PlanFeature::create(['plan_id' => $enterprisePlan->id, 'slug' => 'ssl_monitoring', 'name' => 'SSL Monitoring', 'value' => 'true', 'sort_order' => 3]);
        PlanFeature::create(['plan_id' => $enterprisePlan->id, 'slug' => 'email_alerts', 'name' => 'Email Alerts', 'value' => '10', 'sort_order' => 4]);
    }
}
