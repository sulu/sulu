// @flow
import './global.scss';
import {observer} from 'mobx-react';
import React from 'react';
import Router from '../../services/Router';
import SplitView from '../SplitView';
import Toolbar from '../Toolbar';
import ViewRenderer from '../ViewRenderer';
import applicationStyles from './application.scss';

type Props = {
    router: Router,
};

@observer
export default class Application extends React.PureComponent<Props> {
    render() {
        const {router} = this.props;
        return (
            <div>
                <Toolbar />
                <main className={applicationStyles.main}>
                    {router.route &&
                        <ViewRenderer
                            key={router.route.name}
                            name={router.route.view}
                            router={router} />
                    }
                </main>
                <SplitView />
            </div>
        );
    }
}
