import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * Logistics company registration (resources/views/logistics/company/index.blade.php).
 * Registered before Alpine.start() so x-data="logisticsCompanyRegistrationForm(...)" always resolves.
 */
window.logisticsCompanyRegistrationForm = function (companyType, members, sharedCompanyType) {
    return {
        companyType,
        members,
        sharedCompanyType,
        addMember() {
            this.members.push({ first_name: '', last_name: '', phone: '', email: '' });
        },
        removeMember(i) {
            if (this.members.length > 1) {
                this.members.splice(i, 1);
            }
        },
        onCompanyTypeChange() {
            if (this.companyType === this.sharedCompanyType && this.members.length === 0) {
                this.members.push({ first_name: '', last_name: '', phone: '', email: '' });
            }
        },
    };
};

Alpine.start();
