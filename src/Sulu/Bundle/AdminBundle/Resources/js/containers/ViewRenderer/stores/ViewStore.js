// @flow
class ViewStore {
    map: {[string]: ReactClass<*>};

    constructor() {
        this.map = {};
    }

    add(name: string, view: ReactClass<*>) {
        if (name in this.map) {
            throw new Error('The key "' + name + '" has already been used for another view');
        }

        this.map[name] = view;
    }

    get(name: string): ReactClass<*> {
        return this.map[name];
    }
}

export default new ViewStore();
