// @flow
import {action, autorun, observable, computed} from 'mobx';
import jsonPointer from 'json-pointer';
import symfonyRouting from 'fos-jsrouting/router';
import Router from '../../../services/Router';
import Requester from '../../../services/Requester';

export default class BadgeStore {
    router: Router;
    routeName: string;
    dataPath: ?string;
    requestParameters: Object;
    routerAttributesToRequest: Object;
    @observable value: ?string = null;
    disposer: () => {};

    constructor(
        router: Router,
        routeName: string,
        dataPath: ?string,
        requestParameters: Object,
        routerAttributesToRequest: Object
    ) {
        this.router = router;
        this.routeName = routeName;
        this.dataPath = dataPath;
        this.requestParameters = requestParameters;
        this.routerAttributesToRequest = routerAttributesToRequest;

        this.disposer = autorun(() => {
            // Needed to tell autorun to listen on route changes
            this.router.route;

            this.load();
        });
    }

    @computed get evaluatedRequestParameters() {
        const {
            router: {
                attributes: routerAttributes,
            },
            requestParameters: attributesToRequest,
            routerAttributesToRequest,
        } = this;

        const requestParameters = {};
        Object.keys(routerAttributesToRequest)
            .forEach((routerAttributeKey) => {
                const requestAttributeKey = routerAttributesToRequest[routerAttributeKey];
                const attributeName = isNaN(routerAttributeKey)
                    ? routerAttributeKey
                    : requestAttributeKey;

                requestParameters[requestAttributeKey] = routerAttributes[attributeName];
            });

        return {...requestParameters, ...attributesToRequest};
    }

    @computed get url() {
        const {routeName} = this;

        return symfonyRouting.generate(routeName, this.evaluatedRequestParameters);
    }

    @action setData(data: any) {
        const {dataPath} = this;

        let enhancedData = data;
        if (dataPath) {
            enhancedData = jsonPointer.get(data, dataPath);
        }

        this.value = String(enhancedData);
    }

    load = () => {
        Requester.get(this.url).then((response: Object) => {
            this.setData(response);
        });
    };

    destroy = () => {
        this.disposer();
    };
}
