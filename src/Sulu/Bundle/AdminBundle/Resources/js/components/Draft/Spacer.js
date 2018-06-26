// @flow
import React from 'react';
import type {Spacer as SpacerProps} from './types';
import spacerStyles from './spacer.scss';

export default class Spacer extends React.Component<SpacerProps> {
    render() {
        return (
            <div className={spacerStyles.spacer} />
        );
    }
}
