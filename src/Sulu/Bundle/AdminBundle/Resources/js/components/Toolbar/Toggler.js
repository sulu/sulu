// @flow
import React from 'react';
import TogglerComponent from '../Toggler';
import Button from './Button';
import type {Toggler as TogglerProps} from './types';

export default class Toggler extends React.Component<TogglerProps> {
    render() {
        const {disabled, label, loading, onClick, skin, value} = this.props;

        return (
            <Button disabled={disabled} loading={loading} onClick={onClick} skin={skin}>
                <TogglerComponent checked={value} onChange={onClick}>
                    {label}
                </TogglerComponent>
            </Button>
        );
    }
}
