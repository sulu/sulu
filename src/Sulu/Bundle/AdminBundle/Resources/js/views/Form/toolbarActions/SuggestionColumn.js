// @flow
import React from 'react';
import {observer} from 'mobx-react';
import FormContainer from '../../../containers/Form';
import suggestionColumnStyles from './suggestionColumn.scss';
import type {FormStoreInterface} from '../../../containers';
import type {Node} from 'react';

const noop = () => undefined;

type Props = {|
    children?: Node,
    heading?: ?string,
    store: ?FormStoreInterface,
|};

/**
 * One column of the generate-with-suggestion dialog: an optional heading above a card, the
 * form fields rendered from a memory form store, and optional extra content below the fields.
 * While the store is not ready yet, the card just stays empty - the regenerate button carries
 * its own loading indicator instead.
 */
@observer
export default class SuggestionColumn extends React.Component<Props> {
    render(): Node {
        const {children, heading, store} = this.props;

        return (
            <div className={suggestionColumnStyles.column}>
                {heading && <div className={suggestionColumnStyles.heading}>{heading}</div>}

                <div className={suggestionColumnStyles.card}>
                    {store && <FormContainer onSubmit={noop} store={store} />}

                    {children}
                </div>
            </div>
        );
    }
}
