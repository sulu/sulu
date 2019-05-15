// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import ContactDetails from '../ContactDetails';
import Email from '../../ContactDetails/Email';
import Fax from '../../ContactDetails/Fax';
import Phone from '../../ContactDetails/Phone';
import SocialMedia from '../../ContactDetails/SocialMedia';
import Website from '../../ContactDetails/Website';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

beforeEach(() => {
    Email.types = [
        {label: 'Work', value: 1},
        {label: 'Private', value: 2},
    ];

    Fax.types = [
        {label: 'Work', value: 1},
        {label: 'Private', value: 2},
    ];

    Phone.types = [
        {label: 'Work', value: 1},
        {label: 'Private', value: 2},
    ];

    SocialMedia.types = [
        {label: 'Facebook', value: 1},
        {label: 'Twitter', value: 2},
    ];

    Website.types = [
        {label: 'Work', value: 1},
        {label: 'Private', value: 2},
    ];
});

test('Render empty ContactDetails', () => {
    expect(render(<ContactDetails onBlur={jest.fn()} onChange={jest.fn()} />)).toMatchSnapshot();
});

test('Render empty phone and email fields even if other values are set', () => {
    const value = {
        emails: [],
        faxes: [{fax: '230985230', faxType: 1}],
        phones: [],
        socialMedia: [{socialMediaType: 1, username: 'test'}],
        websites: [{website: 'http://www.sulu.io', websiteType: 1}],
    };

    expect(render(<ContactDetails onBlur={jest.fn()} onChange={jest.fn()} value={value} />)).toMatchSnapshot();
});

test('Render ContactDetails with data', () => {
    const value = {
        emails: [{email: 'test@example.org', emailType: 2}],
        faxes: [{fax: '20937439', faxType: 2}],
        phones: [{phone: '20937439', phoneType: 1}],
        socialMedia: [{socialMediaType: 2, username: 'test'}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    };

    expect(render(<ContactDetails onBlur={jest.fn()} onChange={jest.fn()} value={value} />)).toMatchSnapshot();
});

test('Add data should call onChange and onBlur callbacks', () => {
    const blurSpy = jest.fn();
    const changeSpy = jest.fn();

    const contactDetails = shallow(<ContactDetails onBlur={blurSpy} onChange={changeSpy} />);

    contactDetails.find('DropdownButton Action').at(0).simulate('click');
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: undefined, emailType: 1}],
        faxes: [],
        phones: [],
        socialMedia: [],
        websites: [],
    });

    contactDetails.find('DropdownButton Action').at(1).simulate('click');
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [],
        faxes: [],
        phones: [{phone: undefined, phoneType: 1}],
        socialMedia: [],
        websites: [],
    });

    contactDetails.find('DropdownButton Action').at(2).simulate('click');
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [],
        faxes: [],
        phones: [],
        socialMedia: [],
        websites: [{website: undefined, websiteType: 1}],
    });

    contactDetails.find('DropdownButton Action').at(3).simulate('click');
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [],
        faxes: [{fax: undefined, faxType: 1}],
        phones: [],
        socialMedia: [],
        websites: [],
    });

    contactDetails.find('DropdownButton Action').at(4).simulate('click');
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [],
        faxes: [],
        phones: [],
        socialMedia: [{socialMediaType: 1, username: undefined}],
        websites: [],
    });

    expect(blurSpy).toBeCalledTimes(5);
});

test('Add data should also work with predefined email and phone fields', () => {
    const blurSpy = jest.fn();
    const changeSpy = jest.fn();

    const contactDetails = mount(<ContactDetails onBlur={blurSpy} onChange={changeSpy} />);

    contactDetails.find('Email Email').prop('onChange')('test@example.org');
    expect(changeSpy).toBeCalledWith({
        emails: [{email: 'test@example.org', emailType: 1}],
        faxes: [],
        phones: [],
        socialMedia: [],
        websites: [],
    });

    contactDetails.find('Phone Phone').prop('onChange')('1098509');
    expect(changeSpy).toBeCalledWith({
        emails: [{email: 'test@example.org', emailType: 1}],
        faxes: [],
        phones: [{phone: '1098509', phoneType: 1}],
        socialMedia: [],
        websites: [],
    });
});

test('Remove data should call the onChange and onBlur callbacks', () => {
    const value = {
        emails: [{email: 'test@example.org', emailType: 1}],
        faxes: [{fax: '20937439', faxType: 1}],
        phones: [{phone: '20937439', phoneType: 1}],
        socialMedia: [{username: 'test', socialMediaType: 1}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    };

    const blurSpy = jest.fn();
    const changeSpy = jest.fn();

    const contactDetails = mount(<ContactDetails onBlur={blurSpy} onChange={changeSpy} value={value} />);

    contactDetails.find('Email Icon.removeIcon').prop('onClick')();
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [],
        faxes: [{fax: '20937439', faxType: 1}],
        phones: [{phone: '20937439', phoneType: 1}],
        socialMedia: [{socialMediaType: 1, username: 'test'}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    contactDetails.find('Fax Icon.removeIcon').prop('onClick')();
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'test@example.org', emailType: 1}],
        faxes: [],
        phones: [{phone: '20937439', phoneType: 1}],
        socialMedia: [{username: 'test', socialMediaType: 1}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    contactDetails.find('Phone Icon.removeIcon').prop('onClick')();
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'test@example.org', emailType: 1}],
        faxes: [{fax: '20937439', faxType: 1}],
        phones: [],
        socialMedia: [{username: 'test', socialMediaType: 1}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    contactDetails.find('SocialMedia Icon.removeIcon').prop('onClick')();
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'test@example.org', emailType: 1}],
        faxes: [{fax: '20937439', faxType: 1}],
        phones: [{phone: '20937439', phoneType: 1}],
        socialMedia: [],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    contactDetails.find('Website Icon.removeIcon').prop('onClick')();
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'test@example.org', emailType: 1}],
        faxes: [{fax: '20937439', faxType: 1}],
        phones: [{phone: '20937439', phoneType: 1}],
        socialMedia: [{socialMediaType: 1, username: 'test'}],
        websites: [],
    });

    expect(blurSpy).toBeCalledTimes(5);
});

test('Edit data should call the onChange and onBlur callbacks', () => {
    const value = {
        emails: [{email: 'test@example.org', emailType: 1}],
        faxes: [{fax: '20937439', faxType: 1}],
        phones: [{phone: '20937439', phoneType: 1}],
        socialMedia: [{socialMediaType: 1, username: 'test'}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    };

    const blurSpy = jest.fn();
    const changeSpy = jest.fn();

    const contactDetails = mount(<ContactDetails onBlur={blurSpy} onChange={changeSpy} value={value} />);

    contactDetails.find('Email Email').prop('onChange')('bla@example.org');
    contactDetails.find('Email Email').prop('onBlur')();
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'bla@example.org', emailType: 1}],
        faxes: [{fax: '20937439', faxType: 1}],
        phones: [{phone: '20937439', phoneType: 1}],
        socialMedia: [{socialMediaType: 1, username: 'test'}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    contactDetails.find('Fax Phone').prop('onChange')('0923850');
    contactDetails.find('Fax Phone').prop('onBlur')();
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'bla@example.org', emailType: 1}],
        faxes: [{fax: '0923850', faxType: 1}],
        phones: [{phone: '20937439', phoneType: 1}],
        socialMedia: [{socialMediaType: 1, username: 'test'}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    contactDetails.find('Phone Phone').prop('onChange')('123590');
    contactDetails.find('Phone Phone').prop('onBlur')();
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'bla@example.org', emailType: 1}],
        faxes: [{fax: '0923850', faxType: 1}],
        phones: [{phone: '123590', phoneType: 1}],
        socialMedia: [{socialMediaType: 1, username: 'test'}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    contactDetails.find('SocialMedia').prop('onUsernameChange')(0, 'bla');
    contactDetails.find('SocialMedia').prop('onBlur')();
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'bla@example.org', emailType: 1}],
        faxes: [{fax: '0923850', faxType: 1}],
        phones: [{phone: '123590', phoneType: 1}],
        socialMedia: [{socialMediaType: 1, username: 'bla'}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    contactDetails.find('Website Url').prop('onChange')('http://example.org');
    contactDetails.find('Website Url').prop('onBlur')();
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'bla@example.org', emailType: 1}],
        faxes: [{fax: '0923850', faxType: 1}],
        phones: [{phone: '123590', phoneType: 1}],
        socialMedia: [{socialMediaType: 1, username: 'bla'}],
        websites: [{website: 'http://example.org', websiteType: 1}],
    });

    expect(blurSpy).toBeCalledTimes(5);
});

test('Changing the types should call the onChange and onBlur callbacks', () => {
    const value = {
        emails: [{email: 'test@example.org', emailType: 1}],
        faxes: [{fax: '20937439', faxType: 1}],
        phones: [{phone: '20937439', phoneType: 1}],
        socialMedia: [{socialMediaType: 1, username: 'test'}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    };

    const blurSpy = jest.fn();
    const changeSpy = jest.fn();

    const contactDetails = mount(<ContactDetails onBlur={blurSpy} onChange={changeSpy} value={value} />);

    contactDetails.find('Email ArrowMenu button').simulate('click');
    contactDetails.find('Email ArrowMenu Item[value=2]').simulate('click');
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'test@example.org', emailType: 2}],
        faxes: [{fax: '20937439', faxType: 1}],
        phones: [{phone: '20937439', phoneType: 1}],
        socialMedia: [{socialMediaType: 1, username: 'test'}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    contactDetails.find('Fax ArrowMenu button').simulate('click');
    contactDetails.find('Fax ArrowMenu Item[value=2]').simulate('click');
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'test@example.org', emailType: 2}],
        faxes: [{fax: '20937439', faxType: 2}],
        phones: [{phone: '20937439', phoneType: 1}],
        socialMedia: [{socialMediaType: 1, username: 'test'}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    contactDetails.find('Phone ArrowMenu button').simulate('click');
    contactDetails.find('Phone ArrowMenu Item[value=2]').simulate('click');
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'test@example.org', emailType: 2}],
        faxes: [{fax: '20937439', faxType: 2}],
        phones: [{phone: '20937439', phoneType: 2}],
        socialMedia: [{socialMediaType: 1, username: 'test'}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    contactDetails.find('SocialMedia ArrowMenu button').simulate('click');
    contactDetails.find('SocialMedia ArrowMenu Item[value=2]').simulate('click');
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'test@example.org', emailType: 2}],
        faxes: [{fax: '20937439', faxType: 2}],
        phones: [{phone: '20937439', phoneType: 2}],
        socialMedia: [{socialMediaType: 2, username: 'test'}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    contactDetails.find('Website ArrowMenu button').simulate('click');
    contactDetails.find('Website ArrowMenu Item[value=2]').simulate('click');
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'test@example.org', emailType: 2}],
        faxes: [{fax: '20937439', faxType: 2}],
        phones: [{phone: '20937439', phoneType: 2}],
        socialMedia: [{socialMediaType: 2, username: 'test'}],
        websites: [{website: 'http://www.example.org', websiteType: 2}],
    });

    expect(blurSpy).toBeCalledTimes(5);
});
