// @flow
class ViewStore {
    map: {[string]: ReactClass<*>};

    constructor() {
        this.map = {};
    }

    add(name: string, view: ReactClass<*>) {
        this.map[name] = view;
    }

    get(name: string): ReactClass<*> {
        return this.map[name];
    }
}

export default new ViewStore();
