// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {ResourceLocatorHistory} from 'sulu-route-bundle/containers';
import CustomUrlComponent from '../../../components/CustomUrl';
import customUrlStyles from './customUrl.scss';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';

@observer
class CustomUrl extends React.Component<FieldTypeProps<Array<?string>>> {
    handleChange = (value: Array<?string>) => {
        const {onChange} = this.props;

        onChange(value);
    };

    handleBlur = () => {
        const {onFinish} = this.props;

        onFinish();
    };

    render() {
        const {formInspector, value} = this.props;

        const baseDomain = formInspector.getValueByPath('/baseDomain');

        if (typeof baseDomain !== 'string') {
            throw new Error('The baseDomain should be a string. This should not happen and is likely a bug.');
        }

        return (
            <div className={customUrlStyles.customUrlContainer}>
                <div className={customUrlStyles.customUrl}>
                    <CustomUrlComponent
                        baseDomain={baseDomain}
                        onBlur={this.handleBlur}
                        onChange={this.handleChange}
                        value={value || []}
                    />
                </div>
                {formInspector.id &&
                    <div className={customUrlStyles.resourceLocatorHistory}>
                        <ResourceLocatorHistory
                            id={formInspector.id}
                            options={{webspace: formInspector.options.webspace}}
                            resourceKey="custom_url_routes"
                        />
                    </div>
                }
            </div>
        );
    }
}

export default CustomUrl;
