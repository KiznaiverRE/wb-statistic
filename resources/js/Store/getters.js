export default {
    errorMessage: state => {
        console.log('Getter: errorMessage', state.errorMessage);
        return state.errorMessage;
    },
};
