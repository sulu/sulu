// @flow

class InternalLinkTypeRegistry {
    titles: {[string]: string};

    constructor() {
        this.clear();
    }

    clear() {
        this.titles = {};
    }

    add(name: string, title: string) {
        if (name in this.titles) {
            throw new Error('The key "' + name + '" has already been used for another internal link type');
        }

        this.titles[name] = title;
    }

    getKeys(): Array<string> {
        return Object.keys(this.titles);
    }

    getTitle(name: string): string {
        if (!(name in this.titles)) {
            throw new Error('There is no title for an internal link type with the key "' + name + '" registered');
        }

        return this.titles[name];
    }
}

export default new InternalLinkTypeRegistry();
