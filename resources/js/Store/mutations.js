export default {
    setErrorMessage(state, message) {
        console.log('Mutation: setErrorMessage', message);
        state.errorMessage = message;
    },
    clearErrorMessage(state) {
        console.log('Mutation: clearErrorMessage');
        state.errorMessage = '';
    },
};
