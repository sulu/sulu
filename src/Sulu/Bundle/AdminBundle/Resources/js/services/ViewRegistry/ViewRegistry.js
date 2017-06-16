// @flow
const map: {[string]: ReactClass<*>} = {};

function addView(name: string, view: ReactClass<*>) {
    map[name] = view;
}

function getView(name: string): ReactClass<*> {
    return map[name];
}

export {addView, getView};
