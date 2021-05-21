// @flow
import {action, reaction, observable, computed} from 'mobx';
import jsonPointer from 'json-pointer';
import debounce from 'debounce';
import symfonyRouting from 'fos-jsrouting/router';
import Router, {Route} from '../../../services/Router';
import Requester from '../../../services/Requester';
import type {HandleResponseHook} from '../../../services/Requester/types';

export default class BadgeStore {
    router: Router;
    routeName: string;
    dataPath: ?string;
    requestParameters: Object;
    routerAttributesToRequest: Object;
    tabViewRoute: Route;
    @observable value: ?string = null;
    routeChangeDisposer: () => {};

    constructor(
        router: Router,
        routeName: string,
        dataPath: ?string,
        requestParameters: Object,
        routerAttributesToRequest: Object,
        tabViewRoute: Route
    ) {
        this.router = router;
        this.routeName = routeName;
        this.dataPath = dataPath;
        this.requestParameters = requestParameters;
        this.routerAttributesToRequest = routerAttributesToRequest;
        this.tabViewRoute = tabViewRoute;

        this.load();

        // Needed to tell autorun to listen on route changes
        this.routeChangeDisposer = reaction(() => this.router.route, () => {
            this.load();
        });

        if (!Requester.handleResponseHooks.includes(this.responseHook)) {
            Requester.handleResponseHooks.push(this.responseHook);
        }
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

    @computed get isChildOrSameRoute() {
        let route: ?Route = this.router.route;
        while (route !== this.tabViewRoute) {
            if (!route) {
                return false;
            }

            route = route.parent;
        }

        return true;
    }

    load = debounce(() => {
        if (!this.isChildOrSameRoute) {
            return;
        }

        Requester.get(this.url).then((response: Object) => {
            this.setData(response);
        });
    }, 3000, true);

    responseHook: HandleResponseHook = (response: Response, options: ?Object) => {
        if (!options || typeof options.method === 'undefined') {
            return;
        }

        if (response.url.includes(this.url)) {
            return;
        }

        if (response.url.includes('/admin/api/collaborations')) {
            return;
        }

        if (response.url.includes('/admin/preview/')) {
            return;
        }

        if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(options.method.toUpperCase())) {
            this.load();
        }
    };

    destroy = () => {
        this.routeChangeDisposer();

        if (Requester.handleResponseHooks.includes(this.responseHook)) {
            Requester.handleResponseHooks.splice(
                Requester.handleResponseHooks.indexOf(this.responseHook),
                1
            );
        }
    };
}
