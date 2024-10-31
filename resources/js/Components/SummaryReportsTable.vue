<style>
.data div {
    margin-bottom: 1px;
}
</style>

<template>
    <div class="pb-3">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex flex-col p-5" v-if="rows.data && Object.keys(rows).length">
                <!--                 -->

                <div class="font-semibold text-gray-900 mb-5 font-bold">Отчёты</div>
                <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">

                        <div class="pb-3 mb-6 border-b-2" v-for="(row, rowIndex) in rows.data" :key="rowIndex">
                            <!--                             -->

                            <div class="min-w-full w-full">
                                <div class="meta mr-5 mb-6 pr-4">

                                    <div class="text-2xl font-bold block">{{row.meta.name}}</div>
                                    <div class="text-lg block text-gray-600">Категория: {{row.meta.category}}</div>
                                    <div class="text-lg block text-gray-600">Артикул продавца: {{rowIndex}}</div>
                                    <div class="text-lg block text-gray-600">Артикул WB: {{row.meta.wb_article}}</div>
                                </div>
                                <div class="reports-wrapper flex">
                                    <div style="width: 220px;" class="reports-titles mr-5 pr-4">
                                        <div class="reports-title font-bold mt-6 border-b">к перечеслению</div>
                                        <div class="reports-title font-bold border-b">количество заказов</div>
                                        <div class="reports-title font-bold border-b">средний чек</div>
                                        <div class="reports-title font-bold border-b">закупочня цена</div>
                                        <div class="reports-title font-bold border-b">логистика</div>
                                        <div class="reports-title font-bold border-b">логистика % </div>
                                        <div class="reports-title font-bold border-b">хранение </div>
                                        <div class="reports-title font-bold border-b">реклама </div>
                                        <div class="reports-title font-bold border-b">ДРР % </div>
                                        <div class="reports-title font-bold border-b">Штрафы </div>
                                        <div class="reports-title font-bold border-b">придет на счет </div>
                                        <div class="reports-title font-bold border-b">себес партии</div>
                                        <div class="reports-title font-bold border-b">прибыль</div>
                                        <div class="reports-title font-bold border-b">прибыль %</div>
                                        <div class="reports-title font-bold border-b">наценка после расходов</div>
                                        <div class="reports-title font-bold border-b">количество возвратов</div>
                                    </div>
                                    <div class="reports flex" v-for="(report, reportIndex) in row.reports">
                                        <div style="width: 300px;" class="report mr-5 pr-4">
                                            <div class="date font-semibold">{{reportIndex}}</div>
                                            <div class="data">
                                                <div>{{report.transfers}}</div>
                                                <div>{{report.orders_count}}</div>
                                                <div>{{report.average_check}}</div>
                                                <div>{{report.purchase_cost}}</div>
                                                <div>{{report.logistic_cost}}</div>
                                                <div>{{report.logistic_percent}}</div>
                                                <div>{{report.storage_cost}}</div>
                                                <div>{{report.advertising_cost}}</div>
                                                <div>{{report.ddr_percent}}</div>
                                                <div>{{report.fine}}</div>
                                                <div>{{report.credited_to_account}}</div>
                                                <div>{{report.batch_cost}}</div>
                                                <div>{{report.profit}}</div>
                                                <div>{{report.profit_percent}}</div>
                                                <div>{{report.margin_after_expenses}}</div>
                                                <div>{{report.returns_count}}</div>
                                            </div>
                                            <button @click="downloadReport(rowIndex, reportIndex)" class="bg-transparent hover:bg-blue-500 text-blue-700 font-semibold hover:text-white py-2 px-4 border border-blue-500 hover:border-transparent rounded">
                                                Скачать
                                            </button>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</template>

<script>
export default {
    name: "SummaryReportsTable",
    data(){
        return{
            rows: [],
        }
    },
    created() {
        this.getData();
    },
    methods: {
        getData(){
            axios.get('/reports/product/get')
                .then(response => {
                    this.rows = response.data;
                    console.log(response.data)
                })
        },
        downloadReport(rowIndex, reportIndex){

        }
    },
}
</script>

<style scoped>

</style>
