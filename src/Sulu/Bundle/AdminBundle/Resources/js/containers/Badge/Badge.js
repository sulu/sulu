// @flow
import React from 'react';
import {observer} from 'mobx-react';
import Router from '../../services/Router';
import BadgeComponent from '../../components/Badge';
import BadgeStore from './stores/BadgeStore';

type Props = {|
    attributesToRequest: Object,
    dataPath: ?string,
    routeName: string,
    router: Router,
    routerAttributesToRequest: Object,
    visibleCondition: ?string,
|};

@observer
class Badge extends React.Component<Props> {
    static defaultProps = {
        attributesToRequest: {},
        dataPath: null,
        routerAttributesToRequest: {},
        visibleCondition: null,
    };

    store: BadgeStore;

    constructor(props: Props) {
        super(props);

        const {
            router,
            routeName,
            dataPath,
            visibleCondition,
            attributesToRequest,
            routerAttributesToRequest,
        } = this.props;

        this.store = new BadgeStore(
            router,
            routeName,
            dataPath,
            visibleCondition,
            attributesToRequest,
            routerAttributesToRequest
        );
    }

    componentWillUnmount() {
        this.store.destroy();
    }

    render() {
        if (!this.store || this.store.data === null || this.store.data === undefined) {
            return null;
        }

        return <BadgeComponent>{this.store.data}</BadgeComponent>;
    }
}

export default Badge;
