// @flow
class ResourceMetadataStore {
    endpoints: {[string]: string} = {};

    configurationPromises: {[string]: Promise<Object>} = {};

    clear() {
        this.endpoints = {};
        this.configurationPromises = {};
    }

    setEndpoints(endpoints: {[string]: string}) {
        this.endpoints = endpoints;
    }

    getEndpoint(key: string) {
        if (!(key in this.endpoints)) {
            throw new Error('There is no endpoint for the resourceKey "' + key + '"');
        }
        return this.endpoints[key];
    }
}

export default new ResourceMetadataStore();
