// @flow
import React from 'react';
import dividerStyles from './divider.scss';

export default class Divider extends React.PureComponent<{||}> {
    render() {
        return <li className={dividerStyles.divider} />;
    }
}
