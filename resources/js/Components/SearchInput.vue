<template>
    <div class="p-5 flex items-center w-1/2">
        <div class="relative w-full">
            <input
                type="text"
                v-model="searchQuery"
                @input="handleInput"
                placeholder="Поиск..."
                class="pl-10 pr-4 py-2 w-full border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            />
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <button
                v-if="searchQuery"
                @click="clearSearch"
                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </div>
</template>

<script>
export default {
name: "SearchInput",
    data(){
        return {
            searchQuery: '',
        }
    },
    methods: {
        handleInput(){
            console.log('this.searchQuery в компоненте'+this.searchQuery)
            this.$emit('search', this.searchQuery)
        },
        clearSearch(){
            this.$emit('search', '')
        }
    }
}
</script>

<style scoped>

</style>
