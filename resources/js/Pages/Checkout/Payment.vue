<script setup>
import { onMounted, ref } from 'vue';

const props = defineProps({
    paymentData: Object,
});

const formRef = ref(null);

onMounted(() => {
    // Автоматично відправляємо форму
    if (formRef.value) {
        formRef.value.submit();
    }
});
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900">
        <div class="text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p class="text-gray-700 dark:text-gray-300">Redirecting to payment...</p>

            <!-- WayForPay Form -->
            <form
                ref="formRef"
                method="POST"
                action="https://secure.wayforpay.com/pay"
                accept-charset="utf-8"
            >
                <input type="hidden" name="merchantAccount" :value="paymentData.merchantAccount">
                <input type="hidden" name="merchantDomainName" :value="paymentData.merchantDomainName">
                <input type="hidden" name="orderReference" :value="paymentData.orderReference">
                <input type="hidden" name="orderDate" :value="paymentData.orderDate">
                <input type="hidden" name="amount" :value="paymentData.amount">
                <input type="hidden" name="currency" :value="paymentData.currency">
                <input type="hidden" name="productName[]" :value="paymentData.productName[0]">
                <input type="hidden" name="productCount[]" :value="paymentData.productCount[0]">
                <input type="hidden" name="productPrice[]" :value="paymentData.productPrice[0]">
                <input type="hidden" name="clientEmail" :value="paymentData.clientEmail">
                <input type="hidden" name="clientFirstName" :value="paymentData.clientFirstName">
                <input type="hidden" name="clientLastName" :value="paymentData.clientLastName">
                <input type="hidden" name="language" :value="paymentData.language">
                <input type="hidden" name="returnUrl" :value="paymentData.returnUrl">
                <input type="hidden" name="serviceUrl" :value="paymentData.serviceUrl">
                <input type="hidden" name="merchantSignature" :value="paymentData.merchantSignature">
            </form>
        </div>
    </div>
</template>
