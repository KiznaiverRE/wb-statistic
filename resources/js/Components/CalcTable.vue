<template>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 pb-0 font-semibold text-gray-900">Загрузить файл</div>

                <div class="file-drag-drop">
                    <label class="label">
                        <span class="drop-files">Выбрать файл</span>
                        <input class="excelInput" type="file" @change="handleFileUpload" >
                    </label>
                </div>


                <div class="flex flex-col p-5" v-if="rows && Object.keys(rows).length !== 0 || newRows && Object.keys(newRows).length">
                    <div class="font-semibold text-gray-900 mb-5 font-bold">Отчёты</div>
                    <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                        <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                            <button @click="saveData" type="button" class="mb-5 inline-block items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Сохранить
                            </button>
                            <div class="scroll-wrapper">
                                <div class="scrollbar-top" id="scrollbar-top"></div>
                                <div id="table-wrapper" class="table-wrapper shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">

                                    <div class="bg-slate-200 rounded-md" v-for="(row, rowIndex) in newRows" :key="rowIndex">
                                        <div class="text-xl font-bold inline-block">{{rowIndex}}</div>
                                        <div class="text-xl inline-block">{{row}}</div>
                                    </div>

                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                                v-for="(header, index) in headers" :key="index">{{ header }}</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                                        </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="(row, rowIndex) in rows" :key="rowIndex">
                                            <!-- Рендеринг meta -->
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                                                v-for="(cell, cellIndex) in row.meta" :key="cellIndex">{{ cell }}
                                            </td>
                                            <!-- Рендеринг prices &ndash;&gt;-->
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

                    </div>
                </div>


            </div>
            <div class="mt-4">
                <div class="font-semibold text-gray-900 mb-2 font-bold">Итого:</div>
                <div class="font-semibold text-red-600 mb-5 font-bold text-2xl">3313</div>
            </div>

        </div>
    </div>
</template>

<script>
    export default {
        name: "CalcTable",
        data() {
            return {
                parsedData: null,
                rows: [],
                headers: [],
                newRows: [],
                editing: false,
                editingIndex: null,
                editingRow: null,
                statDate: null,
                dateRange: null,
                filterButton: false
            };
        },
        methods: {
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
                    const { headers, rows } = response.data;

                    this.newRows.push(...rows);

                    console.log(this.newRows);


                    // const findObjectDifferences = (obj1, obj2) => {
                    //     let differences = {};
                    //     for (let key in obj1) {
                    //         if (!obj2.hasOwnProperty(key)) {
                    //             differences[key] = obj1[key];
                    //         }
                    //     }
                    //     return differences;
                    // };
                    //
                    // const findDifferences = (existingRows, incomingRows) => {
                    //     let differences = [];
                    //     console.log(incomingRows)
                    //     console.log(existingRows)
                    //     for (let incomingRow of incomingRows) {
                    //
                    //         let matchFound = false;
                    //         for (let key in existingRows) {
                    //             let row = existingRows[key];
                    //             if (
                    //                 row.meta['АРТ продавца'] == incomingRow['АРТ продавца'] ||
                    //                 row.meta['Код номенклатуры/арт вб'] == incomingRow['Код номенклатуры/арт вб']
                    //             ) {
                    //                 matchFound = true;
                    //                 let difference = findObjectDifferences(this.sliceObject(incomingRow, 4), row.prices);
                    //                 if (Object.keys(difference).length > 0) {
                    //
                    //                     // Добавить различия в ключах и их значения в нужное место
                    //                     for (let key in difference) {
                    //                         row.prices[key] = difference[key];
                    //                     }
                    //
                    //                     // Добавить новые ключи в массив headers
                    //                     for (let key in difference) {
                    //                         if (!this.headers.includes(key)) {
                    //                             this.headers.push(key);
                    //                         }
                    //                     }
                    //                 }
                    //                 break;
                    //             }
                    //         }
                    //         if (!matchFound) {
                    //             console.log('No matches');
                    //             differences.push(incomingRow);
                    //         }
                    //     }
                    //     return differences;
                    // };
                    //
                    // let differences = findDifferences(this.rows, this.newRows);
                    // console.log("Unique differences:", differences);

                    // this.getData();

                    // Если заголовки изменились, можно их обновить
                    if (Object.keys(this.headers).length === 0) {
                        this.headers = headers;
                    }

                } catch (error) {
                    console.error('Error uploading file:', error);
                }
            },
        },
    }
</script>

<style scoped>

</style>
