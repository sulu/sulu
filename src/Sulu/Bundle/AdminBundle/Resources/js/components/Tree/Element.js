// @flow
import {observer} from 'mobx-react';
import React from 'react';
import Checkbox from '../Checkbox';

@observer
export default class Element extends React.PureComponent<Props> {

    render() {
        const {children} = this.props;

        return (
            <div>
                <Checkbox
                    skin="dark"
                    value={0}
                    checked={false}
                    onChange={() => {}}
                />

                {children}
            </div>
        );
    }
}
