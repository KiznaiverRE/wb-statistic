<template>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="inline-block mr-3 border-4 sm:top-50 sm:right-50 p-6">
                <NavLink class="text-2xl p-5 font-semibold
                    text-gray-600 hover:text-gray-900
                    dark:text-gray-400 dark:hover:text-white"
                     :href="route('report.category')" :active="route().current('report.category')">Отчёт по категориям</NavLink>
            </div>

            <div class="inline-block mr-3 border-4 sm:top-50 sm:right-50 p-6">
                <NavLink class="text-2xl p-5 font-semibold
                    text-gray-600 hover:text-gray-900
                    dark:text-gray-400 dark:hover:text-white"
                   :href="route('report.date')" :active="route().current('report.date')">Отчёт по дате</NavLink>
            </div>

            <div class="inline-block mr-3 border-4 sm:top-50 sm:right-50 p-6">
                <NavLink class="text-2xl p-5 font-semibold
                    text-gray-600 hover:text-gray-900
                    dark:text-gray-400 dark:hover:text-white"
                   :href="route('report.summary')" :active="route().current('report.summary')">Отчёт по всем товарам</NavLink>
            </div>
        </div>
    </div>
</template>

<script>
import NavLink from "@/Components/NavLink.vue";

export default {
name: "FinalTable",
    components: {NavLink}
}
</script>

<style scoped>

</style>
