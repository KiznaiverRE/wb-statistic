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
                                    <!-- Добавьте отладочную информацию -->
                                    <div class="text-xl font-bold block">{{rowIndex}}</div>
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
                                                <div class="flex">
                                                    <span v-if="!report.editingAdvertisingCost" class="mr-2">{{report.advertising_cost}}</span>
                                                    <input v-else v-model="report.editedAdvertisingCost" class="border p-1 mr-2" type="text" />
                                                    <button v-if="!report.editingAdvertisingCost" @click="toggleEdit(report, 'advertising_cost')" class="text-blue-700">
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                             fill="none"
                                                             viewBox="0 0 24 24"
                                                             stroke-width="1.5"
                                                             stroke="currentColor" class="size-6">
                                                            <path stroke-linecap="round"
                                                                  stroke-linejoin="round"
                                                                  d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                        </svg>
                                                    </button>
                                                    <button v-if="report.editingAdvertisingCost" @click="saveEdit(report, row.meta.category_id, 'advertising_cost')" class="bg-transparent hover:bg-blue-500 text-blue-700 font-semibold hover:text-white py-2 px-4 border border-blue-500 hover:border-transparent rounded">
                                                        Сохранить
                                                    </button>
                                                </div>
                                                <div>{{report.ddr_percent}}</div>
                                                <div class="flex">
                                                    <span v-if="!report.editingFine" class="mr-2">{{report.fine}}</span>
                                                    <input v-else v-model="report.editedFine" class="border p-1 mr-2" type="text" />
                                                    <button v-if="!report.editingFine" @click="toggleEdit(report, 'fine')" class="text-blue-700">
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                             fill="none"
                                                             viewBox="0 0 24 24"
                                                             stroke-width="1.5"
                                                             stroke="currentColor" class="size-6">
                                                            <path stroke-linecap="round"
                                                                  stroke-linejoin="round"
                                                                  d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                        </svg>
                                                    </button>
                                                    <button v-if="report.editingFine" @click="saveEdit(report, row.meta.category_id, 'fine')" class="bg-transparent hover:bg-blue-500 text-blue-700 font-semibold hover:text-white py-2 px-4 border border-blue-500 hover:border-transparent rounded">
                                                        Сохранить
                                                    </button>
                                                </div>
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
    name: "CategoryReportsTable",
    data(){
        return{
            rows: [],
            isEditing: false,
            editedFine: null,
            editedAdvertisingCost: null
        }
    },
    created() {
      this.getData();
    },
    methods: {
        getData(){
            axios.get('/reports/category/get')
                .then(response => {
                    this.rows = response.data;
                    console.log(response.data)
                })
        },
        downloadReport(rowIndex, reportIndex){

        },
        toggleEdit(report, field) {
            if (field === 'fine') {
                report.editingFine = !report.editingFine;
                if (report.editingFine) {
                    report.editedFine = report.fine;
                }
            } else if (field === 'advertising_cost') {
                report.editingAdvertisingCost = !report.editingAdvertisingCost;
                if (report.editingAdvertisingCost) {
                    report.editedAdvertisingCost = report.advertising_cost;
                }
            }
        },
        saveEdit(report, categoryId, field) {
            if (field === 'fine') {
                report.oldFine = report.fine;
                report.fine = report.editedFine;
                report.editingFine = false;
            } else if (field === 'advertising_cost') {
                report.oldAdvertisingCost = report.advertising_cost;
                report.advertising_cost = report.editedAdvertisingCost;
                report.editingAdvertisingCost = false;
            }

            axios.post('/reports/category/update', {
                'report': report,
                'categoryId': categoryId
            })
                .then(response => {
                    console.log(response.data)
                    this.getData();
                })
        },
    },
}
</script>

<style scoped>

</style>
