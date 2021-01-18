// @flow
import {observable, action, autorun} from 'mobx';
import log from 'loglevel';
import jexl from 'jexl';
import jsonPointer from 'json-pointer';
import symfonyRouting from 'fos-jsrouting/router';
import Router from '../../../services/Router';
import Requester from '../../../services/Requester';

export default class BadgeStore {
    router: Router;
    routeName: string;
    dataPath: ?string;
    visibleCondition: ?string;
    attributesToRequest: Object;
    routerAttributesToRequest: Object;
    @observable data: ?any = null;
    disposer: () => {};

    constructor(
        router: Router,
        routeName: string,
        dataPath: ?string,
        visibleCondition: ?string,
        attributesToRequest: Object,
        routerAttributesToRequest: Object
    ) {
        this.router = router;
        this.routeName = routeName;
        this.dataPath = dataPath;
        this.visibleCondition = visibleCondition;
        this.attributesToRequest = attributesToRequest;
        this.routerAttributesToRequest = routerAttributesToRequest;

        this.disposer = autorun(() => {
            this.load();
        });
    }

    load = () => {
        const {
            router: {
                attributes: routerAttributes,
            },
            routeName,
            attributesToRequest,
            routerAttributesToRequest,
        } = this;

        let requestAttributes = {};
        Object.keys(routerAttributesToRequest)
            .forEach((routerAttributeKey) => {
                const requestAttributeKey = routerAttributesToRequest[routerAttributeKey];
                const attributeName = isNaN(routerAttributeKey)
                    ? routerAttributeKey
                    : requestAttributeKey;

                requestAttributes[requestAttributeKey] = routerAttributes[attributeName];
            });
        requestAttributes = {...requestAttributes, ...attributesToRequest};

        const url = symfonyRouting.generate(routeName, requestAttributes);
        Requester.get(url)
            .then((response: Object) => {
                this.setData(response);
            })
            .catch((response: Object) => {
                log.error(response.message);
            });
    };

    @action setData(data: any) {
        const {dataPath, visibleCondition} = this;

        let enhancedData = data;
        if (dataPath !== null) {
            enhancedData = jsonPointer.get(data, dataPath);
        }
        const text = enhancedData.toString();

        if (visibleCondition !== null) {
            const result = jexl.evalSync(visibleCondition, {text});

            if (!result) {
                this.data = null;

                return;
            }
        }

        this.data = text;
    }

    destroy = () => {
        this.disposer();
    };
}
