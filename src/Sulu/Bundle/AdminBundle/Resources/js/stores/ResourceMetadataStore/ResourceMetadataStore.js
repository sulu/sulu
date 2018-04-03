// @flow
import Requester from '../../services/Requester';

class ResourceMetadataStore {
    endpoints: {[string]: string} = {};

    setEndpoints(endpoints: {[string]: string}) {
        this.endpoints = endpoints;
    }

    configurationPromises: {[string]: Promise<Object>} = {};

    getEndpoint(key: string) {
        if (!(key in this.endpoints)) {
            throw new Error('There is no endpoint for the resourceKey "' + key + '"');
        }
        return this.endpoints[key];
    }

    loadConfiguration(key: string): Promise<Object> {
        if (!(key in this.configurationPromises)) {
            this.configurationPromises[key] = Requester.get('/admin/resources/' + key);
        }

        return this.configurationPromises[key];
    }
}

export default new ResourceMetadataStore();
