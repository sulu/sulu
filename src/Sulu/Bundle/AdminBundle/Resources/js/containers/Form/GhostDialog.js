// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import Dialog from '../../components/Dialog';
import SingleSelect from '../../components/SingleSelect';
import {translate} from '../../utils/Translator';
import ghostDialogStyles from './ghostDialog.scss';

type Props = {
    locales: Array<string>,
    onCancel: () => void,
    onConfirm: (locale: string) => void,
    open: boolean,
};

@observer
export default class GhostDialog extends React.Component<Props> {
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
                cancelText={translate('sulu_admin.no')}
                onCancel={this.handleCancel}
                confirmText={translate('sulu_admin.yes')}
                onConfirm={this.handleConfirm}
                open={open}
                title={translate('sulu_admin.ghost_dialog_title')}
            >
                <div className={ghostDialogStyles.ghostDialog}>
                    <p>{translate('sulu_admin.ghost_dialog_description')}</p>
                    <label className={ghostDialogStyles.label}>{translate('sulu_admin.choose_language')}</label>
                    <div className={ghostDialogStyles.localeSelect}>
                        <SingleSelect onChange={this.handleLocaleChange} value={this.selectedLocale}>
                            {locales.map((locale) => (
                                <SingleSelect.Option key={locale} value={locale}>
                                    {locale}
                                </SingleSelect.Option>
                            ))}
                        </SingleSelect>
                    </div>
                </div>
            </Dialog>
        );
    }
}
