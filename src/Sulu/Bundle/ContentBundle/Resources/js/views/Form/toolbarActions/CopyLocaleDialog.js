// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import {Checkbox, Dialog} from 'sulu-admin-bundle/components';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import {translate} from 'sulu-admin-bundle/utils';
import copyLocaleDialogStyles from './copyLocaleDialog.scss';

type Props = {
    concreteLocales: Array<string>,
    id: string | number,
    locale: string,
    locales: Array<string>,
    onClose: () => void,
    open: boolean,
    webspace: string,
};

@observer
export default class CopyLocaleDialog extends React.Component<Props> {
    @observable copying: boolean = false;
    @observable selectedLocales: Array<string> = [];

    @action componentDidUpdate(prevProps: Props) {
        if (this.props.open === false && prevProps.open === true) {
            this.selectedLocales = [];
        }
    }

    @action handleConfirm = () => {
        this.copying = true;

        const {
            id,
            locale,
            webspace,
        } = this.props;

        ResourceRequester.postWithId(
            'pages',
            id,
            undefined,
            {
                locale,
                dest: this.selectedLocales,
                action: 'copy-locale',
                webspace,
            }
        ).then(action(() => {
            this.copying = false;
            this.props.onClose();
        }));
    };

    @action handleCheckboxChange = (checked: boolean, value?: string | number) => {
        if (checked && typeof value === 'string' && !this.selectedLocales.includes(value)) {
            this.selectedLocales.push(value);
        } else {
            this.selectedLocales.splice(this.selectedLocales.findIndex((locale) => locale === value), 1);
        }
    };

    render() {
        const {concreteLocales, locales, onClose, open} = this.props;

        return (
            <Dialog
                confirmLoading={this.copying}
                cancelText={translate('sulu_admin.cancel')}
                confirmText={translate('sulu_admin.ok')}
                onCancel={onClose}
                onConfirm={this.handleConfirm}
                open={open}
                title={translate('sulu_admin.copy_locale')}
            >
                <div className={copyLocaleDialogStyles.dialog}>
                    <p>{translate('sulu_admin.choose_target_locale')}:</p>
                    {locales.map((locale) => this.props.locale === locale
                        ? null
                        : <Checkbox
                            checked={this.selectedLocales.includes(locale)}
                            key={locale}
                            onChange={this.handleCheckboxChange}
                            value={locale}
                        >
                            {locale}{concreteLocales && !concreteLocales.includes(locale) && '*'}
                        </Checkbox>
                    )}
                    <p>{translate('sulu_admin.copy_locale_dialog_description')}</p>
                </div>
            </Dialog>
        );
    }
}
