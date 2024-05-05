<template>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 pb-0 font-semibold text-gray-900">Загрузить отчёт</div>

                <div class="file-drag-drop">
                    <form ref="fileform">
                        <span class="drop-files">Drop the files here!</span>
                    </form>
                </div>
                <div class="p-6 pb-0 font-semibold text-gray-900">Отчёты</div>
                <div class="table-block">

                    <table>
                        <thead>
                        <tr>
                            <td>Колонка 1</td>
                            <td>Колонка 2</td>
                            <td>Колонка 3</td>
                            <td>Колонка 4</td>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>1</td>
                            <td>2</td>
                            <td>3</td>
                            <td>4</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</template>

<style>
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
