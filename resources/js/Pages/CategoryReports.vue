<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import CategoryReportsTable from "@/Components/CategoryReportsTable.vue";
import NavLink from "@/Components/NavLink.vue";


</script>


<template>
    <Head title="Итоговые отчёты по категории" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Итоговые отчёты по категории</h2>
        </template>
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <NavLink class="mr-2" :href="route('report.category')" :active="route().current('report.category')">Отчёт по категориям</NavLink>
            <NavLink class="mr-2" :href="route('report.date')" :active="route().current('report.date')">Отчёт по дате</NavLink>
            <NavLink class="mr-2" :href="route('report.summary')" :active="route().current('report.summary')">Отчёт по всем товарам</NavLink>
        </div>

        <CategoryReportsTable />
    </AuthenticatedLayout>
</template>

<script>

export default {
    name: "CategoryReports",
    components: {NavLink}
}
</script>

<style scoped>

</style>
