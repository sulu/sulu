// @flow
import BackButton from './BackButton';
import DefaultButton from './DefaultButton';
import DropdownButton from './DropdownButton';
import Icon from '../../components/Icon';
import React from 'react';
import {observer} from 'mobx-react';
import toolbarStore from './stores/ToolbarStore';
import toolbarStyles from './toolbar.scss';

@observer
export default class Toolbar extends React.PureComponent<*> {
    render() {
        return (
            <header className={toolbarStyles.toolbar}>
                <nav>
                    <div className={toolbarStyles.pullLeft}>
                        {toolbarStore.hasBackButtonConfig() &&
                            <BackButton {...toolbarStore.getBackButtonConfig()} />
                        }
                        <div className={toolbarStyles.buttons}>
                            {toolbarStore.hasButtonsConfig() &&
                                toolbarStore.getButtonsConfig().map((buttonConfig) => {
                                    if (buttonConfig.options) {
                                        return <DropdownButton key={buttonConfig.value} {...buttonConfig} />;
                                    } else {
                                        return <DefaultButton key={buttonConfig.value} {...buttonConfig} />;
                                    }
                                })
                            }
                        </div>
                    </div>
                    <div className={toolbarStyles.pullRight}>
                        {toolbarStore.hasIconsConfig() &&
                            <div className={toolbarStyles.icons}>
                                {
                                    toolbarStore.getIconsConfig().map((icon) => (
                                        <Icon key={icon} name={icon} className={toolbarStyles.icon} />
                                    ))
                                }
                            </div>
                        }
                        {toolbarStore.hasLocaleConfig() &&
                            <div className={toolbarStyles.locale}>
                                <DropdownButton
                                    size={'small'}
                                    setValueOnChange={true}
                                    {...toolbarStore.getLocaleConfig()} />
                            </div>
                        }
                    </div>
                </nav>
            </header>
        );
    }
}
