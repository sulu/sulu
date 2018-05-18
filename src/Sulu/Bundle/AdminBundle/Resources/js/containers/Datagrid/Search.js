// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {action, computed, observable} from 'mobx';
import Input from '../../components/Input/Input';

type Props = {
    onSearch: (search: ?string) => void,
};

@observer
export default class Search extends React.Component<Props> {
    @observable expanded: boolean = false;
    @observable value: ?string = undefined;

    @action setExpanded(expanded: boolean) {
        this.expanded = expanded;
    }

    @action setValue(value: ?string) {
        this.value = value;
    }

    @computed get isExpanded(): boolean {
        if (this.value && this.value.length > 0) {
            return true;
        }

        return this.expanded;
    }

    handleChange = (value: ?string) => {
        this.setValue(value);
    };

    handleKeyPress = (event: SyntheticEvent<HTMLInputElement>) => {
        if (event.key === 'Enter') {
            this.handleSearch();
        }
    };

    handleSearch = () => {
        this.props.onSearch(this.value);
    };

    handleIconClick = () => {
        this.setExpanded(!this.expanded);
    };

    render() {
        return (
            <Input
                icon="su-search"
                expanded={this.expanded}
                onChange={this.handleChange}
                onIconClick={this.handleIconClick}
                onKeyPress={this.handleKeyPress}
                skin="dark"
                value={this.value}
            />
        );
    }
}
