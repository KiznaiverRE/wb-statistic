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

                <div style="overflow-x: scroll;" v-if="parsedData && parsedData.length > 0">
                    <div class="p-6 pb-0 font-semibold text-gray-900">Отчёты</div>
                    <table class="table-block">
                        <thead>
                        <tr>
                            <th v-for="(value, key) in parsedData[0]" :key="key">{{ key }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(row, index) in parsedData" :key="index">
                            <td v-for="(value, key) in row" :key="key">{{ value }}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</template>

<script>
import { read, utils } from 'xlsx';

export default {
    data() {
        return {
            parsedData: null
        };
    },
    methods: {
        handleFileUpload(event) {
            const file = event.target.files[0];
            const reader = new FileReader();

            reader.onload = (e) => {
                const data = new Uint8Array(e.target.result);
                const workbook = read(data, { type: 'array' });
                const sheetName = workbook.SheetNames[0];
                const sheet = workbook.Sheets[sheetName];

                // Получаем заголовки из первой строки
                const headers = utils.sheet_to_json(sheet)[0];

                // Преобразуем все элементы первой строки в строки
                for (let i = 0; i < headers.length; i++) {
                    if (typeof headers[i] !== 'string') {
                        headers[i] = headers[i].toString();
                    }
                }

                // Преобразуем остальные строки в объекты с ключами из заголовков
                const parsedData = utils.sheet_to_json(sheet, { header: headers, raw: false, defval: "" });

                console.log(parsedData);

                this.parsedData = parsedData;
                this.saveData(parsedData);
            };

            reader.readAsArrayBuffer(file);
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
        saveData(data) {
            axios.post('upload-excel', data)
            .then(response => {
                console.log('Данные успешно отправлены:', response.data);
            })
            .catch(error => {
                console.error('Произошла ошибка при отправке данных:', error);
            });
        }
    }
};
</script>
