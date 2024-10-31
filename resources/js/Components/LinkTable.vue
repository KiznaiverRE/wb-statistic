<style>
    template{
        display: block !important;
    }
</style>

<template>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-5">
                    <div class="flex mb-3">
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
                                <span>Загрузить файл со связями</span>

                                <input accept=".xlsx, .xls" class="excelInput" type="file" @change="handleFileUpload" >
                            </label>
                        </div>

                        <!-- Добавляем компонент поиска -->
                        <SearchInput @search="handleSearch" />
                    </div>
                    <div class="flex items-center mb-3 justify-between">
                        <div class="p-5 block w-auto">
                            <label class="dp__pointer w-100 align-middle select-none font-sans font-bold text-center uppercase transition-all disabled:opacity-50
                    disabled:shadow-none disabled:pointer-events-none text-xs py-3 px-6 bg-transparent
                    hover:bg-blue-500 text-blue-700 font-semibold hover:text-white py-2 px-4 border border-blue-500 hover:border-transparent rounded flex items-center gap-3"
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
                            </label>
                        </div>
                    </div>
                </div>


                <div class="flex flex-col p-5" v-if="filteredRows && Object.keys(filteredRows).length !== 0">
                    <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                        <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">

                            <div class="scroll-wrapper">
                                <div id="table-wrapper" class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                                    <table>
                                        <thead>
                                            <tr>
                                                <td>Название</td>
                                                <td>Артикул продавца</td>
                                                <td>Код номенклатуры</td>
                                                <td>Категория</td>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(row, rowIndex) in filteredRows" :key="rowIndex">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <input type="text" v-model="row.meta['title']" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                                    <span v-if="errors[rowIndex] && errors[rowIndex]['title']" class="text-red-500 text-sm">{{ errors[rowIndex]['title'] }}</span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <input type="text" v-model="row.meta['sellers_article']" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                                    <span v-if="errors[rowIndex] && errors[rowIndex]['sellers_article']" class="text-red-500 text-sm">{{ errors[rowIndex]['sellers_article'] }}</span>
                                                </td>

                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <input type="text" v-model="row.meta['wb_article']" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                                    <span v-if="errors[rowIndex] && errors[rowIndex]['wb_article']" class="text-red-500 text-sm">{{ errors[rowIndex]['wb_article'] }}</span>
                                                </td>

                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <input type="text" v-model="row.meta['category']" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                                    <span v-if="errors[rowIndex] && errors[rowIndex]['category']" class="text-red-500 text-sm">{{ errors[rowIndex]['category'] }}</span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <button @click="saveLink(row, rowIndex)" class="text-indigo-600 hover:text-indigo-900">Сохранить</button>
                                                </td>
                                            </tr>
                                            <tr v-if="newRow">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <input type="text" v-model="newRow['title']" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                                    <span v-if="errors.newRow && errors.newRow['title']" class="text-red-500 text-sm">{{ errors.newRow['title'] }}</span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <input type="text" v-model="newRow['sellers_article']" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                                    <span v-if="errors.newRow && errors.newRow['sellers_article']" class="text-red-500 text-sm">{{ errors.newRow['sellers_article'] }}</span>
                                                </td>


                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <input type="text" v-model="newRow['sellers_article']" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                                    <span v-if="errors.newRow && errors.newRow['sellers_article']" class="text-red-500 text-sm">{{ errors.newRow['sellers_article'] }}</span>
                                                </td>

                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <input type="text" v-model="newRow['sellers_article']" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                                    <span v-if="errors.newRow && errors.newRow['sellers_article']" class="text-red-500 text-sm">{{ errors.newRow['sellers_article'] }}</span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <button @click="saveLink(newRow)" class="text-indigo-600 hover:text-indigo-900">Сохранить</button>
                                                </td>
                                            </tr>
                                        </tbody>

                                    </table>
                                </div>
                            </div>
                            <div class="flex justify-center mt-4 flex-wrap p-4">
                                <span v-for="page in pages" :key="page" class="m-1">
                                    <button @click="goToPage(page)" :class="{ 'bg-blue-500 text-white': page === pagination.current_page, 'bg-white text-blue-500': page !== pagination.current_page }" class="px-4 py-2 border border-blue-500 rounded-lg transition duration-300 ease-in-out hover:bg-blue-500 hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-opacity-50">{{ page }}
                                    </button>
                                </span>
                            </div>
                            <button @click="addRow" type="button" class="mb-5 mt-5 inline-block items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Добавить связь
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>

import SearchInput from "@/Components/SearchInput.vue";
export default {
    name: "LinkTable",
    components: {SearchInput},
    data() {
        return {
            headers: ['Название', 'Артикул продавца'],
            rows: [],
            pagination: {
                current_page: 5,
                last_page: 1,
                per_page: 50,
                total: 0,
                next_page_url: null,
                prev_page_url: null,
            },
            newRow: null,
            newRows: [],
            errors: {},
            selectedDate: null,
            searchQuery: '', // Добавляем поле для поиска
            message: null
        };
    },
    computed: {
        filteredData() {
            return this.selectedDate
                ? this.data.filter(
                    (item) => item.date === this.selectedDate
                )
                : this.data;
        },
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
            console.log(pages)
            return pages;
        },
    },
    created(){
        this.getData();
    },
    methods: {
        validateRow(row) {
            const errors = {};
            if (!row.meta['title'] || row.meta['title'] === '') {
                errors['title'] = 'Название обязательно для заполнения';
            }
            if (!row.meta['sellers_article'] || row.meta['sellers_article'] === '') {
                errors['sellers_article'] = 'Артикул продавца обязателен для заполнения';
            }
            if (!row.meta['category'] || row.meta['category'] === '') {
                errors['category'] = 'Категория обязательна для заполнения';
            }
            return errors;
        },
        async getData(page = 1) {
            try {
                console.log(page)
                const response = await axios.get('/excel-data', {
                    params: {
                        page: page,
                        search: this.searchQuery
                    }
                }); // Эндпоинт на сервере для получения данных
                const { headers, rows } = response.data.data;
                this.pagination = response.data.pagination

                console.log(rows)
                this.headers = headers;
                this.rows = rows;
            } catch (error) {
                console.error('Error fetching data:', error);
            }
        },
        goToPage(page){
            this.getData(page);
        },
        addRow() {
            this.newRow = { 'Название': '', 'Артикул продавца': '' };
        },
        async saveLink(row, rowIndex = null) {
            const errors = this.validateRow(row);
            if (Object.keys(errors).length > 0) {
                if (rowIndex === null) {
                    this.errors.newRow = errors;
                } else {
                    if (!this.errors[rowIndex]) {
                        this.errors[rowIndex] = {};
                    }
                    this.errors[rowIndex] = errors;
                }
                return;
            } else {
                if (rowIndex !== null) {
                    delete this.errors[rowIndex];
                } else {
                    this.errors.newRow = null;
                }
            }

            try {
                console.log(row);
                const response = await axios.post('/save-link', {
                    product: [row],
                    key: rowIndex
                });
                this.getData();
                console.log('Data saved successfully:', response.data);
            } catch (error) {
                console.error('Error saving data:', error);
            }
        },
        sortDataByDate() {
            this.data.sort((a, b) => new Date(a.date) - new Date(b.date));
        },
        async handleFileUpload(event) {
            const file = event.target.files[0];
            const formData = new FormData();
            formData.append('file', file);

            console.log(formData)

            try {
                const response = await axios.post('/upload-links', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                });
                // const { rows } = response.data;

                console.log(response.data)

                this.getData();

                // Итерируем по rows и добавляем элементы в this.rows
                // for (let key in rows) {
                //     if (rows.hasOwnProperty(key)) {
                        // Добавляем или обновляем элемент в this.rows
                        // if (!this.newRows[key]) {
                        //     this.newRows[key] = rows[key];
                        // }
                        // else {
                        //     // Если элемент уже существует, обновляем его
                        //     for (let subKey in rows[key]) {
                        //         if (rows[key].hasOwnProperty(subKey)) {
                        //             this.rows[key][subKey] = rows[key][subKey];
                        //         }
                        //     }
                        // }
                //     }
                // }
            } catch (error) {
                console.error('Error uploading file:', error);
            }
        },
        async saveLinks(){
            console.log(this.newRows);

            try {
                const response = await axios.post('/save-link', {
                    product: this.newRows,
                });
                this.getData();
                console.log('Data saved successfully:', response.data);
            } catch (error) {
                console.error('Error saving data:', error);
            }
        },
        handleSearch(query){
            this.searchQuery = query;
            this.getData(1);
        },
        clearSearch() {
            this.searchQuery = '';
        },
    },
}
</script>

<style scoped>

</style>
