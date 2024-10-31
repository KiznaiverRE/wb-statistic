

<template>
    <div class="py-12">
        <div v-if="!editing" class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-5">
                    <div class="flex items-center mb-3">
                        <div class="p-5 block w-auto">
                            <label class="dp__pointer w-100 align-middle select-none font-sans font-bold text-center uppercase transition-all disabled:opacity-50
                    disabled:shadow-none disabled:pointer-events-none text-xs py-3 px-6 bg-transparent
                    hover:bg-blue-500 text-blue-700 font-semibold hover:text-white py-2 px-4 border border-blue-500 hover:border-transparent rounded flex items-center gap-3"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                     class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z">
                                    </path>
                                </svg>
                                <span>Загрузить файл</span>

                                <input accept=".xlsx, .xls" class="excelInput" type="file" @change="handleFileUpload" >
                            </label>
                        </div>

                        <!-- Добавляем компонент поиска -->
                        <SearchInput @search="handleSearch" />


                        <div class="p-5 block w-auto">
                            <button @click="saveData" type="button" class="inline-block items-center px-6 py-3 text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Сохранить
                            </button>
                        </div>
                        <div v-if="loading" class="flex justify-center items-center">
                            <div class="loader ease-linear rounded-full border-4 border-t-4 border-gray-200 h-12 w-12"></div>
                        </div>
                    </div>
                    <div class="flex items-center mb-3 justify-between">
                        <div class="p-5 block w-auto">
                            <label class="dp__pointer w-100 align-middle select-none font-sans font-bold text-center uppercase transition-all disabled:opacity-50
                                          disabled:shadow-none disabled:pointer-events-none text-xs py-3 px-6 bg-transparent
                                          hover:bg-blue-500 text-blue-700 font-semibold hover:text-white py-2 px-4 border
                                          border-blue-500 hover:border-transparent rounded flex items-center gap-3"
                            >
                                <svg class="h-5 w-5" width="5" height="5" viewBox="0 0 24 24"
                                     stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                                     stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z"/>
                                    <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"/>
                                    <polyline points="7 11 12 16 17 11"/>
                                    <line x1="12" y1="4" x2="12" y2="16"/>
                                </svg>
                                <span>Скачать шаблон</span>

                                <input accept=".xlsx, .xls" class="excelInput" type="file" @change="handleFileUpload" >
                            </label>
                        </div>
                        <div class="p-5">
                            <template>
                                <VueDatePicker v-model="priceDateRange" range :enable-time-picker="false" :preset-dates="presetDates" @update:model-value="filterPricesByDateRange">
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
                </div>



                <!-- Красивый блок с выводом ошибки -->
                <div v-if="errorMessage" class="m-5 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <p>{{ errorMessage }}</p>
                    <button @click="clearErrorMessage" class="absolute top-0 bottom-0 right-0 px-4 py-3">
                        <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                    </button>
                </div>


                <div class="flex flex-col" v-if="filteredRows && Object.keys(filteredRows).length !== 0 || newRows && Object.keys(newRows).length">
                    <div class="scroll-wrapper p-4">
                        <div id="table-wrapper" class="table-wrapper shadow border-b border-gray-200 sm:rounded-lg">
                            <table ref="tableWrapper" class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        v-for="(header, index) in headers" :key="index">{{ header }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                                </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="(row, rowIndex) in filteredRows" :key="rowIndex">
                                    <!-- Рендеринг meta -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                                        v-for="(cell, cellIndex) in row.meta" :key="cellIndex">{{ cell }}
                                    </td>
                                    <!--                                        &lt;!&ndash; Рендеринг prices &ndash;&gt;-->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                                        v-for="(price, priceIndex) in row.prices" :key="priceIndex">{{ price }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="editRow(rowIndex)" class="text-indigo-600 hover:text-indigo-900">Открыть</button>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="flex justify-center mt-4 flex-wrap p-4">
                    <span v-for="page in pages" :key="page" class="m-1">
                        <button @click="goToPage(page)" :class="{ 'bg-blue-500 text-white': page === pagination.current_page, 'bg-white text-blue-500': page !== pagination.current_page }" class="px-4 py-2 border border-blue-500 rounded-lg transition duration-300 ease-in-out hover:bg-blue-500 hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-opacity-50">{{ page }}</button>
                    </span>
                </div>
            </div>
        </div>
        <div v-else class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="p-6 bg-white shadow-sm sm:rounded-lg">
                <h2 class=" mb-3 text-lg font-medium text-gray-900">Редактирование отчёта</h2>
                <div class="flex justify-end mb-6">
                    <button @click="" v-if="filterButton" class="mr-2 bg-indigo-600 text-white px-4 py-2 rounded-md">Показать</button>
                    <div class="mr-5">
                        <template>
                            <VueDatePicker v-model="date" range :enable-time-picker="false" :preset-dates="presetDates" @update:model-value="filterByDateRange">
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

                    <select @change="filterByOption">
                        <option value="" disabled selected>Выберите дату</option>
                        <option v-for="(cell, cellIndex) in editingRow.prices" :key="cellIndex" :value="cellIndex">{{ cellIndex }}</option>
                    </select>
                </div>
                <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" v-for="(cell, cellIndex) in editingRow.meta" :key="cellIndex">{{ cellIndex }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="mt-4" v-for="(cell, cellIndex) in editingRow.meta" :key="cellIndex">
                                    <input type="text" v-model="editingRow.meta[cellIndex]" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mb-6" v-for="(cell, cellIndex) in editingRow.prices" :key="cellIndex">
                    <div class="p-2 mt-4 mb-2 border flex justify-between items-center">
                        <span class="font-bold mt-1 block w-full sm:text-sm rounded-md">
                            {{ cellIndex }}
                        </span>
                        <span class="mt-1 block w-full sm:text-sm rounded-md">
                            <input type="text" v-model="editingRow.prices[cellIndex]" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </span>
                    </div>
                </div>
                <div class="mt-4 flex">
                    <button @click="saveRow" class="bg-indigo-600 text-white px-4 py-2 rounded-md">Сохранить</button>
                    <button @click="cancelEdit" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md ml-2">Отмена</button>
                </div>
            </div>
        </div>
    </div>

</template>
<script setup>
import { ref } from 'vue';
import { endOfMonth, endOfYear, startOfMonth, startOfYear, subMonths, startOfWeek, endOfWeek, getWeeksInMonth } from 'date-fns';

const date = ref();
const priceDateRange = ref();

const presetDates = ref([
    { label: 'Today', value: [new Date(), new Date()] },
    { label: 'Этот месяц', value: [startOfMonth(new Date()), endOfMonth(new Date())] },
    { label: 'Эта неделя', value: [startOfWeek(new Date()), endOfWeek(new Date())]},
    {
        label: 'Прошлый месяц',
        value: [startOfMonth(subMonths(new Date(), 1)), endOfMonth(subMonths(new Date(), 1))],
    },
    { label: 'Этот год', value: [startOfYear(new Date()), endOfYear(new Date())] },
]);
</script>
<script>
import { read, utils } from 'xlsx';
import ExcelJS from 'exceljs';
import interact from 'interactjs';
import SearchInput from "@/Components/SearchInput.vue";
import {mapState, mapActions} from 'vuex'

export default {
    components: {SearchInput},
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
            headers: [],
            newRows: [],
            editing: false,
            editingIndex: null,
            editingRow: null,
            loading: false,
            statDate: null,
            dateRange: null,
            filterButton: false,
            prices: [],
            searchQuery: '',
            message: null,
            dateDilterApplied: false,
        };
    },
    computed: {
        filteredRows() {
            const filtered = {};
            for (const key in this.rows) {
                const row = this.rows[key];
                if (Object.values(row.meta).some(value => {
                    // Проверяем, что value не является null или undefined
                    if (value == null) return false;
                    return value.toString().toLowerCase().includes(this.searchQuery.toLowerCase());
                })) {
                    filtered[key] = row;
                }
            }
            return filtered;
        },
        pages() {
          const pages = [];
          for (let i = 1; i <= this.pagination.last_page; i++ ){
            pages.push(i)
          }
          return pages;
        },
        ...mapState('error', ['errorMessage']),
    },
    created(){
        this.getData();
    },
    mounted() {
        // this.$nextTick(() => {this.makeDraggable()})
        // this.makeDraggable();
    },
    methods: {
        ...mapActions('error', ['setErrorMessage', 'clearErrorMessage']),
        async handleFileUpload(event) {
            const file = event.target.files[0];
            const formData = new FormData();
            formData.append('file', file);


            try {
                const response = await axios.post('/upload-excel', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                });
                let { headers, rows } = response.data;

                this.newRows = rows;

                for (let key in headers) {
                    if (!this.headers.includes(headers[key])) {
                        this.headers.push(headers[key]);
                    }
                }

                this.prices = Object.fromEntries(
                    Object.entries(this.headers).slice(4)
                )

                if (Object.keys(this.rows).length > 0){
                    for (let key in this.rows){
                        if (this.newRows.hasOwnProperty(key)){
                            let newRow = this.newRows[key]
                            let newRowPrices = newRow.prices

                            for (let k in newRowPrices){
                                this.rows[key].prices[k] = newRowPrices[k]
                            }
                        }
                    }
                    for (let key in this.newRows){
                        if (!this.rows.hasOwnProperty(key)){
                            let newRow = this.newRows[key]
                            this.rows[key] = newRow;
                            // this.rows.push(newRow)
                        }
                    }
                }else{
                    this.rows = this.newRows
                }

                for (let key in this.rows){
                    for (let k in this.prices){
                        if (!this.rows[key].prices.hasOwnProperty(this.prices[k])){
                            this.rows[key].prices[this.prices[k]] = Object.values(this.rows[key].prices)[Object.keys(this.rows[key].prices).length-1]
                        }
                    }

                    // new Promise()
                    this.rows[key].prices = this.sortPrices(this.rows[key].prices, true, 'keys');
                    this.setLastPositivePrice(this.rows[key].prices)

                }

                this.headers = this.sortPrices(this.headers, false, 'values');

            } catch (error) {
                this.setErrorMessage('Неверный формат файла, используйте шаблон для загрузки файлов');
                console.error('Error uploading file:', error);
            }
        },
        sortPrices(prices = null, keys = false, type){
            const method = `Object.${type}`
            const sortedPrices = {};

            if (keys){
                const items = eval(method)(prices);
                const sortedDates = this.sortDates(items);
                sortedDates.forEach(key => {
                    sortedPrices[key] = prices[key];
                })

                return sortedPrices;
            }else {
                const firstFourValues = prices.slice(0, 4);
                const remainingValues = prices.slice(4);

                const items = eval(method)(remainingValues);
                const sortedDates = this.sortDates(items)
                return [...firstFourValues, ...sortedDates];
            }


        },
        sortDates(dates){
            const sortedDates = dates.sort((a,b) => {
                const dateA = new Date(a.replace(/^(\d{2}).(\d{2}).(\d{2})$/, '20$3-$2-$1'))
                const dateB = new Date(b.replace(/^(\d{2}).(\d{2}).(\d{2})$/, '20$3-$2-$1'))
                return dateA - dateB
            })
            return sortedDates
        },
        setLastPositivePrice(prices){
            const pricesObjectKeys = Object.keys(prices);
            let lastPositiveValue = null;


            for (let i = pricesObjectKeys.length - 1; i >= 0; i--){
                const key = pricesObjectKeys[i];
                if (prices[key] !== 0){
                    lastPositiveValue = prices[key];
                    break;
                }
            }

            for (let key of pricesObjectKeys){
                if (Number(prices[key]) === 0){
                    prices[key] = lastPositiveValue;
                }
            }
        },
        async getData(page = 1) {
            try {
                this.loading = true;
                const response = await axios.get('/excel-data', {
                    params: {
                        page: page,
                        search: this.searchQuery
                    }
                }); // Эндпоинт на сервере для получения данных
                this.loading = false;
                const { headers, rows } = response.data.data;
                this.pagination = response.data.pagination;


                if (rows && typeof rows === 'object') {
                    // Присваиваем полученные данные переменным состояния компонента
                    this.headers = headers;
                    this.rows = rows;

                    // Применяем фильтрацию, если она была применена
                    console.log(this.priceDateRange)
                    if (this.filterApplied) {
                        console.log(111111111111)
                        this.filterPricesByDateRange(this.priceDateRange);
                    }

                } else {
                    console.error('Data format is incorrect:', rows);
                }
            } catch (error) {
                console.error('Error fetching data:', error);
            }
        },
        filterPricesByDateRange(date){
            this.filterApplied = true; // Устанавливаем флаг, что фильтрация была применена
            for (const key in this.rows){
                const row = this.rows[key]
                axios.post('/filtered-prices', {
                    date: date,
                    id: row.id,
                }).then(response => {
                    this.rows[key].prices = response.data.prices
                })
            }
            // this.getData()
        },
        goToPage(page){
            this.getData(page);
        },
        async saveData() {
            const dataToSend = {};

            // if (this.rows.length === 0) {
                dataToSend.headers = this.headers;
                dataToSend.rows = this.rows;
            // }else {
            //     dataToSend.rows = this.newRows
            // }

            dataToSend.headers = Object.keys(this.headers).reduce((acc, key) => {
                if (this.headers[key] !== null) {
                    acc[key] = this.headers[key];
                }
                return acc;
            }, {});

            try {
                const response = await axios.post('/save-excel', dataToSend);
                console.log('Data saved successfully:', response.data);
            } catch (error) {
                console.error('Error saving data:', error);
            }
        },
        editRow(rowIndex) {
            this.editing = true;
            this.editingIndex = rowIndex;

            // Глубокая копия объекта строки
            this.editingRow = JSON.parse(JSON.stringify(this.rows[rowIndex]));
        },
        async saveRow(){
            if (this.editingIndex !== null) {
                this.rows[this.editingIndex] = this.editingRow;
            }
            try {
                const response = await axios.post('/save-product', {
                    product: this.rows[this.editingIndex],
                    id: this.editingIndex,
                });
                location.reload();
                // this.cancelEdit();
            } catch (error) {
                console.error('Error saving data:', error);
            }
        },
        handleSearch(query){
            this.searchQuery = query;
            this.getData(1);
        },
        clearSearch(query) {
            this.searchQuery = query;
        },
        showMessage(){

        },
        cancelEdit() {
            this.editing = false;
            this.editingIndex = null;
            this.editingRow = null;
        },
        sliceObject(obj, start, end) {
            const slicedEntries = Object.entries(obj).slice(start, end);
            return Object.fromEntries(slicedEntries);
        },
        async filterByDateRange(date){
            try {
                const response = await axios.post('/filtered-prices', {
                    date: date,
                    id: this.editingRow.id,
                });

                this.editingRow.prices = response.data.prices;
                // this.getData();
            } catch (error) {
                console.error('Error getting data:', error);
            }
            // date ? this.filterButton = true : this.filterButton = false;
        },

        filterByOption(event) {
            const selectedDate = event.target.value;
            if (this.editingRow && this.editingRow.prices) {
                const filteredPrices = Object.keys(this.editingRow.prices).reduce((acc, key) => {
                    if (key === selectedDate) {
                        acc[key] = this.editingRow.prices[key];
                    }
                    return acc;
                }, {});

                this.editingRow.prices = filteredPrices;
            }
        },
    }
};

</script>

<style>
.table-wrapper::-webkit-scrollbar{
    background-color: #eeeeee;
    height: 15px;
    border-radius: 5px;
}
.table-wrapper::-webkit-scrollbar-thumb{
    background-color: #ccc;
    border-radius: 5px;
}
.table-wrapper table{
    /*position: absolute;*/
    /*cursor: move;*/
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    border: 1px solid #ccc;
    padding: 8px;
    text-align: left;
}

th {
    background-color: #f2f2f2;
}
</style>
