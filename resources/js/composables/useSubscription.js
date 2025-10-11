import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export default function useSubscription() {
    const page = usePage()

    const user = computed(() => page.props.auth?.user || null)

    const subscription = computed(() => page.props.auth?.subscription || null)

    const hasSubscription = computed(() => !!subscription.value)

    const isActive = computed(() => subscription.value?.is_active === true)

    const isOnTrial = computed(() => subscription.value?.on_trial === true)

    const plan = computed(() => subscription.value ? {
        id: subscription.value.id,
        name: subscription.value.plan_name,
        slug: subscription.value.plan_slug,
        checkInterval: subscription.value.plan_check_interval,
        billingPeriod: subscription.value.billing_period,
        price: subscription.value.price,
        currency: subscription.value.currency,
    } : null)

    const endsAt = computed(() => subscription.value?.ends_at || null)

    return {
        user,
        subscription,
        plan,
        isActive,
        isOnTrial,
        hasSubscription,
        endsAt,
    }
}
