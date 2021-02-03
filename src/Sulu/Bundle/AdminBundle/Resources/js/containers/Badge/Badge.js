// @flow
import React from 'react';
import {computed} from 'mobx';
import {observer} from 'mobx-react';
import jexl from 'jexl';
import Router from '../../services/Router';
import BadgeComponent from '../../components/Badge';
import BadgeStore from './stores/BadgeStore';

type Props = {|
    dataPath: ?string,
    requestParameters: Object,
    routeName: string,
    router: Router,
    routerAttributesToRequest: Object,
    visibleCondition: ?string,
|};

@observer
class Badge extends React.Component<Props> {
    static defaultProps = {
        dataPath: null,
        requestParameters: {},
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
            requestParameters,
            routerAttributesToRequest,
        } = this.props;

        this.store = new BadgeStore(
            router,
            routeName,
            dataPath,
            requestParameters,
            routerAttributesToRequest
        );
    }

    @computed get badgeVisible() {
        const {
            props: {
                visibleCondition,
            },
            store: {
                value,
            },
        } = this;

        if (visibleCondition) {
            return !!jexl.evalSync(visibleCondition, {value});
        }

        return true;
    }

    componentWillUnmount() {
        this.store.destroy();
    }

    render() {
        const {value} = this.store;

        if (value === null || value === undefined || !this.badgeVisible) {
            return null;
        }

        return <BadgeComponent>{value}</BadgeComponent>;
    }
}

export default Badge;
