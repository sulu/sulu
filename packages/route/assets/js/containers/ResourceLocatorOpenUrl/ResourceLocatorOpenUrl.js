// @flow
import React from 'react';
import Button from 'sulu-admin-bundle/components/Button';
import DropdownButton from 'sulu-admin-bundle/components/DropdownButton';
import {translate} from 'sulu-admin-bundle/utils/Translator';

type Props = {|
    className?: string,
    disabled: boolean,
    locale: string,
    resourceId: ?string | number,
    resourceKey: ?string,
    url: string,
    webspaces: Array<string>,
|};

export default class ResourceLocatorOpenUrl extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
    };

    handleOpen = (webspace: string) => {
        const {locale, resourceId, resourceKey, url} = this.props;

        if (!resourceId || !resourceKey) {
            return;
        }

        // the endpoint redirects to the url of the webspace
        const query = new URLSearchParams({locale, resourceId: String(resourceId), resourceKey, webspace});
        window.open(url + '?' + query.toString(), '_blank', 'noopener');
    };

    render() {
        const {className, disabled, resourceId, resourceKey, webspaces} = this.props;
        const label = translate('sulu_admin.open_url');

        if (disabled || !resourceId || !resourceKey || webspaces.length === 0) {
            return <Button className={className} disabled={true} icon="su-link" skin="link">{label}</Button>;
        }

        if (webspaces.length === 1) {
            return (
                <Button
                    className={className}
                    icon="su-link"
                    onClick={this.handleOpen}
                    skin="link"
                    value={webspaces[0]}
                >
                    {label}
                </Button>
            );
        }

        return (
            <DropdownButton className={className} icon="su-link" label={label} skin="link">
                {webspaces.map((webspace) => (
                    <DropdownButton.Item key={webspace} onClick={this.handleOpen} value={webspace}>
                        {webspace}
                    </DropdownButton.Item>
                ))}
            </DropdownButton>
        );
    }
}
