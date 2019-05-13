// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import ContactDetails from '../ContactDetails';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Render empty ContactDetails', () => {
    expect(render(<ContactDetails onBlur={jest.fn()} onChange={jest.fn()} />)).toMatchSnapshot();
});

test('Render ContactDetails with data', () => {
    const value = {
        emails: [{email: 'test@example.org'}],
        faxes: [{fax: '20937439'}],
        phones: [{phone: '20937439'}],
        socialMedia: [{username: 'test'}],
        websites: [{website: 'http://www.example.org'}],
    };

    expect(render(<ContactDetails onBlur={jest.fn()} onChange={jest.fn()} value={value} />)).toMatchSnapshot();
});

test('Add data should call onChange and onBlur callbacks', () => {
    const blurSpy = jest.fn();
    const changeSpy = jest.fn();

    const contactDetails = shallow(<ContactDetails onBlur={blurSpy} onChange={changeSpy} />);

    contactDetails.find('DropdownButton Action').at(0).simulate('click');
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: undefined}],
        faxes: [],
        phones: [],
        socialMedia: [],
        websites: [],
    });

    contactDetails.find('DropdownButton Action').at(1).simulate('click');
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [],
        faxes: [],
        phones: [{phone: undefined}],
        socialMedia: [],
        websites: [],
    });

    contactDetails.find('DropdownButton Action').at(2).simulate('click');
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [],
        faxes: [],
        phones: [],
        socialMedia: [],
        websites: [{website: undefined}],
    });

    contactDetails.find('DropdownButton Action').at(3).simulate('click');
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [],
        faxes: [{fax: undefined}],
        phones: [],
        socialMedia: [],
        websites: [],
    });

    contactDetails.find('DropdownButton Action').at(4).simulate('click');
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [],
        faxes: [],
        phones: [],
        socialMedia: [{username: undefined}],
        websites: [],
    });

    expect(blurSpy).toBeCalledTimes(5);
});

test('Remove data should call the onChange and onBlur callbacks', () => {
    const value = {
        emails: [{email: 'test@example.org'}],
        faxes: [{fax: '20937439'}],
        phones: [{phone: '20937439'}],
        socialMedia: [{username: 'test'}],
        websites: [{website: 'http://www.example.org'}],
    };

    const blurSpy = jest.fn();
    const changeSpy = jest.fn();

    const contactDetails = mount(<ContactDetails onBlur={blurSpy} onChange={changeSpy} value={value} />);

    contactDetails.find('Email Icon.removeIcon').prop('onClick')();
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [],
        faxes: [{fax: '20937439'}],
        phones: [{phone: '20937439'}],
        socialMedia: [{username: 'test'}],
        websites: [{website: 'http://www.example.org'}],
    });

    contactDetails.find('Fax Icon.removeIcon').prop('onClick')();
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'test@example.org'}],
        faxes: [],
        phones: [{phone: '20937439'}],
        socialMedia: [{username: 'test'}],
        websites: [{website: 'http://www.example.org'}],
    });

    contactDetails.find('Phone Icon.removeIcon').prop('onClick')();
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'test@example.org'}],
        faxes: [{fax: '20937439'}],
        phones: [],
        socialMedia: [{username: 'test'}],
        websites: [{website: 'http://www.example.org'}],
    });

    contactDetails.find('SocialMedia Icon.removeIcon').prop('onClick')();
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'test@example.org'}],
        faxes: [{fax: '20937439'}],
        phones: [{phone: '20937439'}],
        socialMedia: [],
        websites: [{website: 'http://www.example.org'}],
    });

    contactDetails.find('Website Icon.removeIcon').prop('onClick')();
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'test@example.org'}],
        faxes: [{fax: '20937439'}],
        phones: [{phone: '20937439'}],
        socialMedia: [{username: 'test'}],
        websites: [],
    });

    expect(blurSpy).toBeCalledTimes(5);
});

test('Edit data should call the onChange and onBlur callbacks', () => {
    const value = {
        emails: [{email: 'test@example.org'}],
        faxes: [{fax: '20937439'}],
        phones: [{phone: '20937439'}],
        socialMedia: [{username: 'test'}],
        websites: [{website: 'http://www.example.org'}],
    };

    const blurSpy = jest.fn();
    const changeSpy = jest.fn();

    const contactDetails = mount(<ContactDetails onBlur={blurSpy} onChange={changeSpy} value={value} />);

    contactDetails.find('Email Email').prop('onChange')('bla@example.org');
    contactDetails.find('Email Email').prop('onBlur')();
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'bla@example.org'}],
        faxes: [{fax: '20937439'}],
        phones: [{phone: '20937439'}],
        socialMedia: [{username: 'test'}],
        websites: [{website: 'http://www.example.org'}],
    });

    contactDetails.find('Fax Phone').prop('onChange')('0923850');
    contactDetails.find('Fax Phone').prop('onBlur')();
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'bla@example.org'}],
        faxes: [{fax: '0923850'}],
        phones: [{phone: '20937439'}],
        socialMedia: [{username: 'test'}],
        websites: [{website: 'http://www.example.org'}],
    });

    contactDetails.find('Phone Phone').prop('onChange')('123590');
    contactDetails.find('Phone Phone').prop('onBlur')();
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'bla@example.org'}],
        faxes: [{fax: '0923850'}],
        phones: [{phone: '123590'}],
        socialMedia: [{username: 'test'}],
        websites: [{website: 'http://www.example.org'}],
    });

    contactDetails.find('SocialMedia').prop('onUsernameChange')(0, 'bla');
    contactDetails.find('SocialMedia').prop('onBlur')();
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'bla@example.org'}],
        faxes: [{fax: '0923850'}],
        phones: [{phone: '123590'}],
        socialMedia: [{username: 'bla'}],
        websites: [{website: 'http://www.example.org'}],
    });

    contactDetails.find('Website Url').prop('onChange')('http://example.org');
    contactDetails.find('Website Url').prop('onBlur')();
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'bla@example.org'}],
        faxes: [{fax: '0923850'}],
        phones: [{phone: '123590'}],
        socialMedia: [{username: 'bla'}],
        websites: [{website: 'http://example.org'}],
    });

    expect(blurSpy).toBeCalledTimes(5);
});
