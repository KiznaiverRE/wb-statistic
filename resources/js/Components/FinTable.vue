<style scoped>

</style>

<template>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-5">
                <div class="flex mb-3">
                    <div class="p-5 block w-auto flex align-middle">
                        <label class="mr-2 dp__pointer w-100 align-middle select-none font-sans font-bold text-center uppercase transition-all disabled:opacity-50 disabled:shadow-none disabled:pointer-events-none text-xs py-3 px-6 bg-transparent hover:bg-blue-500 text-blue-700 font-semibold hover:text-white py-2 px-4 border border-blue-500 hover:border-transparent rounded flex items-center gap-3"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                 class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z">
                                </path>
                            </svg>
                            <span>Загрузить отчёт</span>

                            <input accept=".xlsx, .xls" class="excelInput" type="file" @change="uploadFinFile" >
                        </label>
                        <div class="block w-auto">
                            <DownloadTemplateButton document-type="finance" text-node="" filename="Шаблон Finance"/>
                        </div>
                    </div>
                    <div class="p-5 block w-auto flex">
                        <label class="mr-2 dp__pointer w-100 align-middle select-none font-sans font-bold text-center uppercase transition-all disabled:opacity-50 disabled:shadow-none disabled:pointer-events-none text-xs py-3 px-6 bg-transparent hover:bg-blue-500 text-blue-700 font-semibold hover:text-white py-2 px-4 border border-blue-500 hover:border-transparent rounded flex items-center gap-3"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                 class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z">
                                </path>
                            </svg>
                            <span>Загрузить данные о рекламе</span>

                            <input accept=".xlsx, .xls" class="excelInput" type="file" @change="uploadAdsFile" >
                        </label>
                        <div class="block w-auto">
                            <DownloadTemplateButton document-type="ads" text-node="" filename="Шаблон Ads"/>
                        </div>
                    </div>
                    <div class="p-5 block w-auto flex">
                        <label class="mr-2 dp__pointer w-100 align-middle select-none font-sans font-bold text-center uppercase transition-all disabled:opacity-50 disabled:shadow-none disabled:pointer-events-none text-xs py-3 px-6 bg-transparent hover:bg-blue-500 text-blue-700 font-semibold hover:text-white py-2 px-4 border border-blue-500 hover:border-transparent rounded flex items-center gap-3"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                 class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z">
                                </path>
                            </svg>
                            <span>Загрузить данные о хранении</span>

                            <input accept=".xlsx, .xls" class="excelInput" type="file" @change="uploadStorageFile" >
                        </label>
                        <div class="block w-auto">
                            <DownloadTemplateButton document-type="storage" text-node="" filename="Шаблон Storage"/>
                        </div>
                    </div>
                    <div v-if="loading" class="flex justify-center items-center">
                        <div class="loader ease-linear rounded-full border-4 border-t-4 border-gray-200 h-12 w-12"></div>
                    </div>
                </div>


                <div v-if="filteredRows && Object.keys(filteredRows).length > 0" class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="p-5 block w-auto">
                            <button @click="deleteAllData" class="p-5 flex items-center bg-transparent hover:bg-red-500 text-red-700 font-semibold
                        hover:text-white py-2 px-4 border border-red-500 hover:border-transparent rounded">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                                <span>Удалить данные</span>
                            </button>
                        </div>
                    </div>

                    <div class="p-5">
                        <template>
                            <VueDatePicker v-model="priceDateRange" range :enable-time-picker="false" :preset-dates="presetDates" @update:model-value="">
                                <template #preset-date-range-button="{ label, value, presetDate }">
                                    <span
                                        role="button"
                                        :tabindex="0"
                                        @click="presetDate(value)"
                                        @keyup.enter.prevent="presetDate(value)"
                                        @keyup.space.prevent="presetDate(value)">
                                      {{ label }}
                                    </span>
                                </template>
                            </VueDatePicker>
                        </template>
                    </div>
                </div>

                <!-- Добавляем компонент поиска -->
                <SearchInput @search="handleSearch" />

                <!-- Красивый блок с выводом ошибки -->
                <div v-if="errorMessage" class="m-5 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <p>{{ errorMessage }}</p>
                    <button @click="clearErrorMessage" class="absolute top-0 bottom-0 right-0 px-4 py-3">
                        <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                    </button>
                </div>



                <div class="flex flex-col p-5" v-if="newRows && Object.keys(newRows).length > 0">
                    <div class="font-semibold text-gray-900 mb-5 font-bold">Отчёты</div>
                    <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                        <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                            <button @click="saveData" type="button" class="mb-5 inline-block items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Сохранить
                            </button>

                            <div class="pb-3 mb-6 border-b-2" style="background-color: rgb(209 250 229);" v-for="(row, rowIndex) in newRows" :key="rowIndex">
                                <div class="min-w-full w-full">
                                    <div class="meta mr-5 mb-6 pr-4">
                                        <div class="text-xl font-bold block">{{row.meta.name}}</div>
                                        <div class="text-xl">Категория: {{row.meta.category}}</div>
                                        <div class="text-xl">Артикул продавца: {{row.meta.article}}</div>
                                        <div class="text-xl">Артикул WB: {{row.meta.wb_article}}</div>
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
                                            <div class="reports-title font-bold border-b">Штраф </div>
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
                                                <div class="data" v-for="(item, itemIndex) in row.reports[reportIndex]">
                                                    <div class="transfers">{{item.transfers}}</div>
                                                    <div class="ordersCount">{{item.ordersCount}}</div>
                                                    <div class="averageCheck">{{item.averageCheck}}</div>
                                                    <div class="cost">{{item.cost}}</div>
                                                    <div class="logistic">{{item.logistic}}</div>
                                                    <div class="logisticPercent">{{item.logisticPercent}}</div>
                                                    <div class="storage">{{item.storage}}</div>
                                                    <div class="ads">{{item.ads}}</div>
                                                    <div class="ddr">{{item.ddr}}</div>
                                                    <div class="fines">{{item.fines}}</div>
                                                    <div class="creditedToAccount">{{item.creditedToAccount}}</div>
                                                    <div class="batchCost">{{item.batchCost}}</div>
                                                    <div class="profit">{{item.profit}}</div>
                                                    <div class="profitPercent">{{item.profitPercent}}</div>
                                                    <div class="margin">{{item.margin}}</div>
                                                    <div class="returns">{{item.returns}}</div>
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


                <div class="flex flex-col p-5" v-if="filteredRows && Object.keys(filteredRows).length > 0">
                    <div class="font-semibold text-gray-900 mb-5 font-bold">Отчёты</div>
                    <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                        <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">

                            <div class="pb-3 mb-6 border-b-2" v-for="(row, rowIndex) in filteredRows" :key="rowIndex">
                                <div class="min-w-full w-full">
                                    <div class="meta mr-5 mb-6 pr-4">
                                        <!-- Добавьте отладочную информацию -->
                                        <div class="text-xl font-bold block">{{row.name}}</div>
                                        <div class="text-xl">Категория: {{row.category}}</div>
                                        <div class="text-xl">Артикул продавца: {{row.seller_article}}</div>
                                        <div class="text-xl">Артикул WB: {{row.wb_article}}</div>
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
                                            <div class="reports-title font-bold border-b">Штраф </div>
                                            <div class="reports-title font-bold border-b">придет на счет </div>
                                            <div class="reports-title font-bold border-b">себес партии</div>
                                            <div class="reports-title font-bold border-b">прибыль</div>
                                            <div class="reports-title font-bold border-b">прибыль %</div>
                                            <div class="reports-title font-bold border-b">наценка после расходов</div>
                                            <div class="reports-title font-bold border-b">количество возвратов</div>
                                        </div>
                                        <div class="reports flex" v-for="(report, reportIndex) in row.financial_reports">
                                            <div style="width: 300px;" class="report mr-5 pr-4">
                                                <div class="date font-semibold">{{report.report_date}}</div>
                                                <div class="data">
                                                    <div>{{report.transfers}}</div>
                                                    <div>{{report.orders_count}}</div>
                                                    <div>{{report.average_check}}</div>
                                                    <div>{{report.purchase_cost}}</div>
                                                    <div>{{report.logistic_cost}}</div>
                                                    <div>{{report.logistic_percent}}</div>
                                                    <div>{{report.storage_cost}}</div>
                                                    <div class="flex">
                                                        <span v-if="!isEditing" class="mr-2">{{report.advertising_cost}}</span>
                                                        <input v-else v-model="editedCost" class="border p-1 mr-2" type="text" />
                                                        <button v-if="!isEditing" @click="toggleEdit(report)" class="text-blue-700">
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
                                                        <button v-if="isEditing" @click="saveEdit(report)" class="bg-transparent hover:bg-blue-500 text-blue-700 font-semibold hover:text-white py-2 px-4 border border-blue-500 hover:border-transparent rounded">
                                                            Сохранить
                                                        </button>
                                                    </div>
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

                            <div class="flex justify-center mt-4 flex-wrap p-4">
                                <span v-for="page in pages" :key="page" class="m-1">
                                    <button @click="goToPage(page)" :class="{ 'bg-blue-500 text-white': page === pagination.current_page, 'bg-white text-blue-500': page !== pagination.current_page }" class="px-4 py-2 border border-blue-500 rounded-lg transition duration-300 ease-in-out hover:bg-blue-500 hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-opacity-50">{{ page }}
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {ref} from "vue";

const date = ref();
import SearchInput from "@/Components/SearchInput.vue";
import DownloadTemplateButton from "@/Components/DownloadTemplateButton.vue";
import {mapState, mapActions} from 'vuex';
import {endOfMonth, endOfWeek, endOfYear, startOfMonth, startOfWeek, startOfYear, subMonths} from "date-fns";
import Echo from "laravel-echo";
import Pusher from "pusher-js";
    export default {
        name: "FinTable",
        components: {
            SearchInput,
            DownloadTemplateButton
        },
        data() {
            return {
                parsedData: null,
                rows: [],
                pagination: {
                    current_page: 1,
                    last_page: 1,
                    per_page: 50,
                    total: 0,
                    next_page_url: null,
                    prev_page_url: null,
                },
                priceDateRange: null,
                presetDates: [
                    { label: 'Today', value: [new Date(), new Date()] },
                    { label: 'Этот месяц', value: [startOfMonth(new Date()), endOfMonth(new Date())] },
                    { label: 'Эта неделя', value: [startOfWeek(new Date()), endOfWeek(new Date())]},
                    {
                        label: 'Прошлый месяц',
                        value: [startOfMonth(subMonths(new Date(), 1)), endOfMonth(subMonths(new Date(), 1))],
                    },
                    { label: 'Этот год', value: [startOfYear(new Date()), endOfYear(new Date())] },
                ],
                headers: [],
                newRows: [],
                editing: false,
                editingIndex: null,
                editingRow: null,
                statDate: null,
                dateRange: null,
                filterButton: false,
                isEditing: false,
                editedCost: null,
                loading: false,
                searchQuery: '',
                message: null,
                isProcessing: false,  // Добавьте это в data или computed
            };
        },
        computed: {
            filteredRows() {
                const filtered = {};
                for (const key in this.rows) {
                    const row = this.rows[key];
                    if (Object.values(row).some(value => {
                        // Проверяем, что value не является null или undefined
                        if (value == null) return false;
                        return value.toString().toLowerCase().includes(this.searchQuery.toLowerCase());
                    })) {
                        filtered[key] = row;
                    }
                }
                return filtered;
            },
            ...mapState('error', ['errorMessage']),
            pages() {
                const pages = [];
                for (let i = 1; i <= this.pagination.last_page; i++ ){
                    pages.push(i)
                }
                return pages;
            },
        },
        created() {
            this.getData();
        },
        methods: {
            ...mapActions('error', ['setErrorMessage', 'clearErrorMessage']),
            async handleFileUpload(event, url, callback) {
                const file = event.target.files[0];
                const formData = new FormData();
                formData.append('file', file);

                // Проверяем, есть ли данные в newRows
                if (this.newRows && this.newRows.length > 0) {
                    formData.append('newRows', JSON.stringify(this.newRows));
                }


                try {
                    this.loading = true;
                    console.log(url)
                    const response = await axios.post(url, formData, {
                        headers: {
                            'Content-Type': 'multipart/form-data'
                        }
                    });


                    // Проверяем, что сервер вернул fileHash
                    if (response.status !== 200 || !response.data.fileHash) {
                        throw new Error('Ошибка при обработке файла на сервере');
                    }

                    const { fileHash } = response.data; // Получаем fileHash
                    console.log(`File Hash: ${fileHash}`);

                    this.isProcessing = true;

                    // Подключение к WebSocket через Laravel Echo
                    this.listenForUpdates(fileHash, callback);

                    // if (response.data) {
                    //     return response.data;
                    // }

                } catch (error) {
                    console.error('Error uploading file:', error);
                    this.setErrorMessage('Неверный формат файла, используйте шаблон для загрузки файлов');
                    event.target.value = '';

                    // Детальный вывод ошибки
                    if (error.response) {
                        // The request was made and the server responded with a status code
                        // that falls out of the range of 2xx
                        console.error('Data:', error.response.data);
                        console.error('Status:', error.response.status);
                        console.error('Headers:', error.response.headers);
                    } else if (error.request) {
                        // The request was made but no response was received
                        console.error('Request:', error.request);
                    } else {
                        // Something happened in setting up the request that triggered an Error
                        console.error('Message:', error.message);
                    }
                    console.error('Config:', error.config);

                    throw error;
                } finally {
                    this.loading = false; // Выключаем индикатор загрузки
                    this.isProcessing = false;  // Можно также установить флаг завершения обработки
                }
            },
            listenForUpdates(fileHash, callback) {
                window.Echo.channel(`excel-processed.${fileHash}`)
                    .listen('.ExcelProcessed', rawData => {
                        console.log('Received WebSocket update:', rawData);
                        console.log('fileHash:', fileHash);

                        try {
                            const fileHash = rawData.data; // Сразу используйте rawData без JSON.parse

                            if (!fileHash) {
                                console.log(fileHash)
                                this.setErrorMessage('Некорректные данные от сервера.');
                                return;
                            }

                            // Теперь выполняем API-запрос для получения полных данных
                            fetch(`/api/excel-data/${fileHash}`)
                                .then(response => response.json())
                                .then(data => {
                                    console.log('data: '+data)
                                    console.log('callback: '+callback)
                                    // if (data.message) {
                                    //     this.setErrorMessage(data.message);
                                    //     return;
                                    // }

                                    // Проверьте, что данные корректны
                                    // if (!data.headers || !data.rows) {
                                    //     this.setErrorMessage('Получены некорректные данные от сервера.');
                                    //     return;
                                    // }

                                    // Передаем данные в callback
                                    if (callback) {
                                        callback(data);
                                    }
                                })
                                .catch(error => {
                                    console.error('Ошибка при получении данных:', error);
                                    this.setErrorMessage('Ошибка получения данных с сервера.');
                                });
                        } catch (error) {
                            console.error('Ошибка при обработке данных:', error);
                            this.setErrorMessage('Ошибка обработки данных от сервера.');
                        }
                    });
            },
            uploadFile(event, url, callback) {
                this.handleFileUpload(event, url, callback)
                    .catch((error) => {
                        console.error(`Ошибка при загрузке файла на URL ${url}:`, error);
                    });
            },
            uploadFinFile(event){
                const url = '/upload-fin';


                this.uploadFile(event, url, (data) => {
                    if (data) {
                        console.log(data)
                        const rows = data;

                        // Преобразование объекта rows в массив
                        const rowsArray = Object.values(rows);

                        //
                        this.newRows.push(...rowsArray);
                    }
                });
            },
            uploadAdsFile(event){
                try {
                    const url = '/upload-ads';

                    this.uploadFile(event, url, (data) => {
                        if (data) {
                            console.log(data)
                            const rows = data;

                            // Очищаем массив newRows перед добавлением новых данных
                            this.newRows = [];

                            // Преобразование объекта rows в массив
                            const rowsArray = Object.values(rows);

                            this.newRows.push(...rowsArray);
                        }
                    });
                } catch (error) {
                    console.error(`Error in uploadStorageFile for URL ${url}:`, error);
                }
            },
            uploadStorageFile(event){
                try {
                    const url = '/upload-storage';

                    this.uploadFile(event, url, (data) => {
                        if (data) {
                            const rows = data;

                            // Очищаем массив newRows перед добавлением новых данных
                            this.newRows = [];

                            // Преобразование объекта rows в массив
                            const rowsArray = Object.values(rows);

                            this.newRows.push(...rowsArray);
                        }
                    });
                } catch (error) {
                    console.error(`Error in uploadStorageFile for URL ${url}:`, error);
                }
            },
            getData(page = 1){
                try {
                    this.loading = true;
                    axios.get('/get-fin', {
                        params: {
                            page: page,
                            search: this.searchQuery
                        }
                    })
                    .then(response => {
                        this.rows = response.data.data.data;
                        this.pagination = response.data.pagination
                        this.loading = false;
                    })
                } catch (error){
                    console.error('Ошибка при загрузке данных:', error)
                }
            },
            goToPage(page){
                this.getData(page)
            },
            saveData(){
                try {
                    axios.post('/save-fin', this.newRows)
                    .then(response => {
                        location.reload();
                    })
                } catch (error){
                    console.error('Ошибка при сохранении данных:', error)
                }
            },
            deleteAllData(){
                axios.post('/delete-fin-data', this.rows)
                .then(response => {
                    this.getData();
                })
            },
            downloadReport(rowIndex, reportIndex){
                axios.post('/download-fin', this.rows[rowIndex].financial_reports[reportIndex],
                    {
                        responseType: 'blob' // Указываем, что ожидаем бинарный файл
                    })
                    .then(response => {
                        const url = window.URL.createObjectURL(new Blob([response.data]));
                        const link = document.createElement('a');
                        link.href = url;
                        link.setAttribute('download', 'Статистика.xlsx'); // Имя файла
                        document.body.appendChild(link);
                        link.click();
                }).catch(error => {
                    console.error('Ошибка при экспорте данных:', error)
                })
            },
            toggleEdit(report) {
                this.isEditing = !this.isEditing;
                if (this.isEditing) {
                    this.editedCost = report.advertising_cost;
                }
            },
            saveEdit(report) {
                report.advertising_cost = this.editedCost;
                this.isEditing = false;

                axios.post('/saveReport', report)
                    .then(response => {
                        // report = response.data;
                        this.getData();
                    })
            },
            handleSearch(query){
                this.searchQuery = query;
                this.getData(1)
            },
        },
    }
</script>

<style scoped>

</style>
