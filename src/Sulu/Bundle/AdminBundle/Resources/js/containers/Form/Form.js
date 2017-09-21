// @flow
import type {ElementRef} from 'react';
import React from 'react';
import Renderer from './Renderer';
import type {Schema} from './types';

type Props = {
    schema: Schema,
    onSubmit: () => void,
};

export default class Form extends React.PureComponent<Props> {
    renderer: ?ElementRef<typeof Renderer>;

    /** @public */
    submit = () => {
        if (!this.renderer) {
            return;
        }

        this.renderer.submit();
    };

    handleSubmit = () => {
        this.props.onSubmit();
    };

    setRenderer = (renderer: ?ElementRef<typeof Renderer>) => {
        this.renderer = renderer;
    };

    render() {
        const {schema} = this.props;
        return (
            <Renderer
                ref={this.setRenderer}
                onSubmit={this.handleSubmit}
                schema={schema}
            />
        );
    }
}
