<style>
label.label {
    display: block;
    height: 300px;
    width: 100%;
    background: #eee;
    text-align: center;
    line-height: 300px;
    border: 1px dotted #ccc;
    border-radius: 10px;
    cursor: pointer;
}

input#excelInput {
    width: 0;
    height: 0;
    opacity: 0;
    pointer-events: none;
    user-select: none;
}

.file-drag-drop{
    margin: 20px 40px;
}
form{
    display: block;
    height: 300px;
    width: 100%;
    background: #eee;

    text-align: center;
    line-height: 300px;
    border: 1px dotted #ccc;
    border-radius: 10px;
}
.table-block{
    margin: 20px 40px;
}
table{
    display: table;
    width: 100%;
    border: 1px solid #ccc;
    /*margin: 40px;*/
}
table td{
    border: 1px solid #ccc;
    padding: 5px;
}
.scroll-wrapper {
    position: relative;
    width: 100%;
}

.scrollbar-top {
    overflow-x: scroll;
    height: 16px; /* Высота полосы прокрутки */
    margin-bottom: -16px; /* Сдвиг вниз, чтобы наложить на таблицу */
}

.table-wrapper {
    overflow-x: scroll;
    overflow-y: hidden;
    width: 100%;
}

.table-wrapper table {
    width: 100%;
}
</style>

<template>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 pb-0 font-semibold text-gray-900">Загрузить отчёт</div>

                <div class="file-drag-drop">
                    <label class="label">
                        <span class="drop-files">Выбрать файл</span>
                        <input id="excelInput" type="file" @change="handleFileUpload" >
                    </label>
                </div>

                <div class="flex flex-col p-5" v-if="rows && rows.length > 0">
                    <div class="font-semibold text-gray-900 mb-5 font-bold">Отчёты</div>
                    <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                        <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                            <button @click="saveData" type="button" class="mb-5 inline-block items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Сохранить
                            </button>
                            <div class="scroll-wrapper">
                                <div class="scrollbar-top" id="scrollbar-top"></div>
                            <div id="table-wrapper" class="table-wrapper shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" v-for="(header, index) in headers" :key="index">{{ header }}</th>
                                    </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="(row, rowIndex) in rows" :key="rowIndex">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" v-for="(cell, cellIndex) in row" :key="cellIndex">{{ cell }}</td>
                                        </tr>
                                    <!-- More people... -->
                                    </tbody>
                                </table>
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
import { read, utils } from 'xlsx';
import ExcelJS from 'exceljs';

export default {
    data() {
        return {
            parsedData: null,
            rows: [],
            headers: []
        };
    },
    methods: {
        async handleFileUpload(event) {
            const file = event.target.files[0];
            console.log(file);
            const formData = new FormData();
            formData.append('file', file);

            try {
                const response = await axios.post('/upload-excel', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                });
                const { headers, rows } = response.data;
                this.headers = headers;
                this.rows = rows;
            } catch (error) {
                console.error('Error uploading file:', error);
            }
        },
        async saveData() {
            try {
                const response = await axios.post('/save-excel', {
                    headers: this.headers,
                    rows: this.rows
                });
                console.log('Data saved successfully:', response.data);
            } catch (error) {
                console.error('Error saving data:', error);
            }
        },
        calculateFormulas(data) {
            for (let i = 1; i < data.length; i++) {
                for (let j = 0; j < data[i].length; j++) {
                    const cellValue = data[i][j];
                    if (typeof cellValue === 'string' && cellValue.startsWith('=')) {
                        // Вычисляем формулу с помощью eval
                        try {
                            const result = eval(cellValue.substring(1)); // Убираем "=" из формулы
                            data[i][j] = result.toString(); // Заменяем формулу на результат вычисления
                        } catch (error) {
                            console.error(`Ошибка вычисления формулы в ячейке (${i}, ${j}):`, error);
                        }
                    }
                }
            }
        },
    }
};
// document.addEventListener('DOMContentLoaded', function () {
//     const topScrollbar = document.getElementById('scrollbar-top');
//     const tableWrapper = document.getElementById('table-wrapper');
//
//     topScrollbar.addEventListener('scroll', function () {
//         tableWrapper.scrollLeft = topScrollbar.scrollLeft;
//     });
//
//     tableWrapper.addEventListener('scroll', function () {
//         topScrollbar.scrollLeft = tableWrapper.scrollLeft;
//     });
//
//     // Создание искусственной полосы прокрутки для верхнего элемента
//     topScrollbar.style.width = tableWrapper.scrollWidth + 'px';
// });
</script>
