// @flow
import DefaultButton from './DefaultButton';
import DropdownButton from './DropdownButton';
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
                    <div className={toolbarStyles.buttonsContainer}>
                        {
                            toolbarStore.getButtonsConfig().map((buttonConfig) => {
                                if (buttonConfig.options) {
                                    return <DropdownButton key={buttonConfig.value} {...buttonConfig} />;
                                } else {
                                    return <DefaultButton key={buttonConfig.value} {...buttonConfig} />;
                                }
                            })
                        }
                    </div>
                </nav>
            </header>
        );
    }
}
