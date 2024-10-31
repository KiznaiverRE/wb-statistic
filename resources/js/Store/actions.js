export default {
    setErrorMessage({commit}, message) {
        commit('setErrorMessage', message);
    },
    clearErrorMessage({commit}) {
        commit('clearErrorMessage');
    },
};
