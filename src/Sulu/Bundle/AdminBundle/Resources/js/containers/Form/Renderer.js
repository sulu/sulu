// @flow
import type {ElementRef} from 'react';
import React from 'react';

type Props = {
    onSubmit: () => void,
};

export default class Renderer extends React.PureComponent<Props> {
    submitButton: ElementRef<'form'>;

    /** @public */
    submit = () => {
        this.submitButton.click();
    };

    handleSubmit = (event: SyntheticEvent<HTMLFormElement>) => {
        this.props.onSubmit();
        event.preventDefault();
    };

    setSubmitButton = (submitButton: ElementRef<'button'>) => {
        this.submitButton = submitButton;
    };

    render() {
        return (
            <form onSubmit={this.handleSubmit}>
                <button ref={this.setSubmitButton} type="submit">Submit</button>
            </form>
        );
    }
}
