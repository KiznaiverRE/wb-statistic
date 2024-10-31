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

                        <div class="pb-3 mb-6 border-b-2" v-for="(row, rowIndex) in rows.data.reports" :key="rowIndex">
                            <!--                             -->

                            <div class="min-w-full w-full">
                                <div class="meta mr-5 mb-6 pr-4">
                                    <!-- Добавьте отладочную информацию -->
                                    <div class="text-xl font-bold block">{{rowIndex}}</div>
                                </div>
                                <div class="reports-wrapper flex">
                                    <div style="width: 220px;" class="reports-titles mr-5 pr-4">
                                        <div class="reports-title font-bold border-b">к перечеслению</div>
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
                                    <div class="reports flex">
                                        <div style="width: 300px;" class="report mr-5 pr-4">
                                            <div class="date font-semibold"></div>
                                            <div class="data">
                                                <div>{{row.transfers}}</div>
                                                <div>{{row.orders_count}}</div>
                                                <div>{{row.average_check}}</div>
                                                <div>{{row.purchase_cost}}</div>
                                                <div>{{row.logistic_cost}}</div>
                                                <div>{{row.logistic_percent}}</div>
                                                <div>{{row.storage_cost}}</div>
                                                <div>{{row.advertising_cost}}</div>
                                                <div>{{row.ddr_percent}}</div>
                                                <div>{{row.fine}}</div>
                                                <div>{{row.credited_to_account}}</div>
                                                <div>{{row.batch_cost}}</div>
                                                <div>{{row.profit}}</div>
                                                <div>{{row.profit_percent}}</div>
                                                <div>{{row.margin_after_expenses}}</div>
                                                <div>{{row.returns_count}}</div>
                                            </div>
                                            <button @click="downloadReport(rowIndex)" class="bg-transparent hover:bg-blue-500 text-blue-700 font-semibold hover:text-white py-2 px-4 border border-blue-500 hover:border-transparent rounded">
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
    name: "DateReportsTable",
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
            axios.get('/reports/date/get')
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
