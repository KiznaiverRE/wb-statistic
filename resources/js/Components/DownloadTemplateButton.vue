<template>
    <button title="Скачать шаблон" @click="getHeaders" class="dp__pointer w-100 align-middle select-none font-sans font-bold text-center uppercase transition-all disabled:opacity-50
                                          disabled:shadow-none disabled:pointer-events-none text-xs py-3 px-6 py-2 px-4 bg-transparent
                                          hover:bg-blue-500 text-blue-700 font-semibold hover:text-white border
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
        <span>{{textNode}}</span>
    </button>
</template>

<script>
import * as XLSX from "xlsx"

export default {
    name: "DownloadTemplateButton",
    props: {
        documentType: {
            type: String,
            required: true
        },
        filename: {
            type: String,
            required: true
        },
        textNode: {
            type: String,
            default: '',
            required: true
        }
    },
    methods: {
        getHeaders(){
            fetch('/storage/template_headers.json')
                .then(response => response.json())
                .then(data => {
                    const headers = data[this.documentType]
                    this.downloadTemplate(headers)
                })
        },
        downloadTemplate(headers){
            // console.log(headers)

            const worksheet = XLSX.utils.aoa_to_sheet([headers]);
            const workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, worksheet, "Шаблон");

            const currentDate = new Date().toISOString().slice(0.10);
            const fullFilename = `${this.filename} ${currentDate}.xlsx`

            XLSX.writeFile(workbook, fullFilename);
        }
    },
}
</script>
