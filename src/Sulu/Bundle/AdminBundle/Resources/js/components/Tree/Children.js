// @flow
import {observer} from 'mobx-react';
import React from 'react';

@observer
export default class Children extends React.PureComponent<Props> {

    render() {
        const {children} = this.props;

        return (
            <ul>
                {children}
            </ul>
        );
    }
}

