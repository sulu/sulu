// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import Dialog from '../../components/Dialog';
import Form from '../../components/Form';
import SingleSelect from '../../components/SingleSelect';
import {translate} from '../../utils/Translator';

type Props = {
    locales: Array<string>,
    onCancel: () => void,
    onConfirm: (locale: string) => void,
    open: boolean,
};

@observer
class GhostDialog extends React.Component<Props> {
    @observable selectedLocale: string;

    constructor(props: Props) {
        super(props);

        this.selectedLocale = this.props.locales[0];
    }

    handleCancel = () => {
        this.props.onCancel();
    };

    handleConfirm = () => {
        this.props.onConfirm(this.selectedLocale);
    };

    @action handleLocaleChange = (locale: string | number) => {
        if (typeof locale !== 'string') {
            throw new Error('Only strings are accepted as locales! This should not happen and is likely a bug.');
        }

        this.selectedLocale = locale;
    };

    render() {
        const {
            locales,
            open,
        } = this.props;

        return (
            <Dialog
                align="left"
                cancelText={translate('sulu_admin.no')}
                confirmText={translate('sulu_admin.yes')}
                onCancel={this.handleCancel}
                onConfirm={this.handleConfirm}
                open={open}
                title={translate('sulu_admin.ghost_dialog_title')}
            >
                <p>{translate('sulu_admin.ghost_dialog_description')}</p>
                <Form>
                    <Form.Field colSpan={6} label={translate('sulu_admin.choose_language')}>
                        <SingleSelect onChange={this.handleLocaleChange} value={this.selectedLocale}>
                            {locales.map((locale) => (
                                <SingleSelect.Option key={locale} value={locale}>
                                    {locale}
                                </SingleSelect.Option>
                            ))}
                        </SingleSelect>
                    </Form.Field>
                </Form>
            </Dialog>
        );
    }
}

export default GhostDialog;
