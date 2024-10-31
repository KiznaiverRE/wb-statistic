<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import FinalReports from "@/Components/FinalReports.vue";
</script>


<template>
    <Head title="Отчёты" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Отчёты</h2>
        </template>

        <FinalReports />
    </AuthenticatedLayout>
</template>

<script>
export default {
    name: "Final"
}
</script>

<style scoped>

</style>
