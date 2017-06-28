// @flow
class ViewStore {
    views: {[string]: ReactClass<*>};

    constructor() {
        this.clear();
    }

    clear() {
        this.views = {};
    }

    add(name: string, view: ReactClass<*>) {
        if (name in this.views) {
            throw new Error('The key "' + name + '" has already been used for another view');
        }

        this.views[name] = view;
    }

    get(name: string): ReactClass<*> {
        return this.views[name];
    }
}

export default new ViewStore();
