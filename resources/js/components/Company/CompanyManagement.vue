<template>
    <a-table
        :columns="columns"
        :row-key="record => record.id"
        :data-source="data"
        :loading="isLoading"
        :pagination="pagination"
        @change="(e) => handleTableChange(e)"
    >
        <template #title>
            <div style="text-align: right">
                <a-button @click="handleCreate">Create new company</a-button>
            </div>
        </template>
        <template #bodyCell="{ column, record }">
            <template v-if="column.key === 'logo_url'">
                <a-avatar :src="record.logo_url"/>
            </template>
            <template v-if="column.key === 'action'">
              <a-button @click="handleEdit(record.id)">Edit</a-button>
                <a-divider type="vertical" />
              <a-button @click="handleDelete(record.id)">Delete</a-button>
            </template>
        </template>
    </a-table>

    <a-modal
        v-model:visible="showEditModal"
        title="Editing"
        :confirm-loading="isEditing"
        :ok-button-props="{disabled: logoErrorUploading}"
        @close="handleCancelForEditModal"
        @cancel="handleCancelForEditModal"
        @ok="handleOkForEditModal"
    >
        <a-form layout="horizontal" :label-col="{ style: { width: '150px' } }" :wrapper-col=" { span: 14 }">
            <a-form-item label="Name">
                <a-input v-model:value="selectedCompany.name" placeholder="input placeholder" />
            </a-form-item>
            <a-form-item label="Email">
                <a-input v-model:value="selectedCompany.email" placeholder="input placeholder" />
            </a-form-item>
            <a-form-item label="Website">
                <a-input v-model:value="selectedCompany.website" placeholder="input placeholder" />
            </a-form-item>
            <a-form-item label="Logo">
                <a-avatar :src="selectedCompany.logo_url"/>
            </a-form-item>
            <a-form-item label="New Logo">
                <a-upload
                    v-model:file-list="selectedCompany.newLogo"
                    name="file"
                    :before-upload="handleLogoValidation"
                    accept="image/png"
                    :multiple="false"
                    max-count="1"
                    @change="handleLogoChange"
                >
                    <a-button>
                        <upload-outlined></upload-outlined>
                        Upload a new logo
                    </a-button>
                </a-upload>
            </a-form-item>
        </a-form>
    </a-modal>

    <a-modal
        v-model:visible="showCreateModal"
        title="Create new company"
        :confirm-loading="isCreating"
        :ok-button-props="{disabled: logoErrorUploadingCreate}"
        @close="handleCancelForCreateModal"
        @cancel="handleCancelForCreateModal"
        @ok="handleOkForCreateModal"
    >
        <a-form layout="horizontal" :label-col="{ style: { width: '150px' } }" :wrapper-col=" { span: 14 }">
            <a-form-item label="Name">
                <a-input v-model:value="newCompany.name" placeholder="input placeholder" />
            </a-form-item>
            <a-form-item label="Email">
                <a-input v-model:value="newCompany.email" placeholder="input placeholder" />
            </a-form-item>
            <a-form-item label="Website">
                <a-input v-model:value="newCompany.website" placeholder="input placeholder" />
            </a-form-item>
            <a-form-item label="Logo">
                <a-upload
                    v-model:file-list="newCompany.logo"
                    name="file"
                    :before-upload="handleLogoValidationCreate"
                    accept="image/png"
                    :multiple="false"
                    max-count="1"
                    @change="handleLogoChangeCreate"
                >
                    <a-button>
                        <upload-outlined></upload-outlined>
                        Upload a logo
                    </a-button>
                </a-upload>
            </a-form-item>
        </a-form>
    </a-modal>

    <a-modal
        v-model:visible="showDeleteModal"
        title="Delete confirmation"
        :confirm-loading="isDeleting"
        @ok="handleOkForDeleteModal"
    >
        <a-form layout="horizontal" :label-col="{ style: { width: '150px' } }" :wrapper-col=" { span: 14 }">
            <a-form-item label="Name">
                <a-input v-model:value="deleteCompany.name" disabled placeholder="input placeholder" />
            </a-form-item>
            <a-form-item label="Email">
                <a-input v-model:value="deleteCompany.email" disabled placeholder="input placeholder" />
            </a-form-item>
            <a-form-item label="Website">
                <a-input v-model:value="deleteCompany.website" disabled placeholder="input placeholder" />
            </a-form-item>
            <a-form-item label="Logo">
                <a-avatar :src="deleteCompany.logo_url"/>
            </a-form-item>
        </a-form>
        <p style="font-weight: bold; color: red">Are you sure you want to delete this Company? - All employees belong to this company will also be deleted from the system!!</p>
    </a-modal>
</template>
<script>
import axios from 'axios';
import {message, Upload} from "ant-design-vue";

export default {
    components: {
    },
    data() {
        return {
            isLoading: false,
            columns: [
                {
                    title: 'Logo',
                    dataIndex: 'logo_url',
                    key: 'logo_url',
                },
                {
                    title: 'Name',
                    dataIndex: 'name',
                    key: 'name',
                    sorter: true,
                    width: '30%',
                },
                {
                    title: 'Email',
                    dataIndex: 'email',
                    key: 'email',
                    sorter: true,
                    width: '30%',
                },
                {
                    title: 'Website',
                    dataIndex: 'website',
                    key: 'website',
                    width: '30%',
                },
                {
                    title: 'Action',
                    key: 'action',
                    width: '10%',
                }
            ],
            data: null,
            total: 0,
            currentPage: 1,
            limit: 10,
            selectedCompany: {},
            newCompany: {},
            deleteCompany:{},
            showEditModal: false,
            showCreateModal: false,
            showDeleteModal: false,
            isEditing: false,
            isCreating: false,
            isDeleting: false,
            logoErrorUploading: false,
            logoErrorUploadingCreate: false,
        }
    },
    created() {
        this.getCompanies();
    },

    computed: {
        pagination() {
            return {
                total: this.total,
                current: this.currentPage,
                pageSize: this.limit,
            }
        },
    },

    methods: {
        getCompanies(limit = 10, page = 1) {
            this.isLoading = true;
            const params = {
                limit,
                page,
            }
            axios.get('/api/company', {params})
                .then((response) => {
                    this.isLoading = false;
                    this.data = response.data.data;
                    this.total = response.data.total;
                    this.currentPage = response.data.current_page;
                })
                .catch((error) => {
                    this.isLoading = false;
                    console.log(error);
                });
        },

        handleTableChange(e) {
            this.getCompanies(this.limit, e.current);
        },

        /**
         *
         *  Method for Delete modal
         *
         */
        handleDelete(id) {
            this.showDeleteModal = true;
            this.deleteCompany = JSON.parse(JSON.stringify(this.data.find((el) => el.id === id)));
        },

        handleOkForDeleteModal() {
            const params = {
                id : this.deleteCompany.id,
            }
            axios.delete('/api/company', {params})
                .then((response) => {
                    this.getCompanies();
                    this.deleteCompany = {};
                    message.success(response.data.message, 3);
                    this.isDeleting = false
                    this.showDeleteModal = false;
                })
                .catch((error) => {
                    this.deleteCompany = {};
                    message.error(error.message, 3);
                });
        },

        /**
         *
         *  Method for Edit modal
         *
         */
        handleEdit(id) {
            this.showEditModal = true;
            this.selectedCompany = JSON.parse(JSON.stringify(this.data.find((el) => el.id === id)));
        },

        handleCancelForEditModal(e) {
            this.showEditModal = false;
        },

        handleOkForEditModal(e) {
            e.preventDefault();
            this.isEditing = false;
            this.showEditModal = false;

            // upload file and call a request to update the selected company
            alert('update');
        },

        handleLogoValidation(file) {
            const isPNG = file.type === 'image/png';
            if (!isPNG) {
                message.error(`${file.name} is not a png file`, 3);
                this.logoErrorUploading = true;
            } else {
                this.logoErrorUploading = false;
            }

            return false;
        },

        handleLogoChange(file, fileList, event) {
            if (this.logoErrorUploading) {
                // remove unaccepted file
                this.selectedCompany.logo = [];
                this.selectedCompany.file = null;
                this.selectedCompany.fileName = null;
            } else {
                if (file && file.file) {
                    this.selectedCompany.file = file.file;
                    this.selectedCompany.fileName = file.file.name;
                }
            }
        },

        /**
         *
         *  Method for Create modal
         *
         */
        handleCreate() {
            this.showCreateModal = true;
        },

        handleCancelForCreateModal(e) {
            this.showCreateModal = false;
            this.newCompany = {};
        },

        handleOkForCreateModal(e) {
            e.preventDefault();
            // upload file and call a request to update the selected company
            const data = new FormData();
            if(this.newCompany.name) {
                data.set('name', this.newCompany.name);
            }

            if(this.newCompany.email) {
                data.set('email', this.newCompany.email);
            }

            if(this.newCompany.website) {
                data.set('website', this.newCompany.website);
            }

            if (this.newCompany.file && this.newCompany.fileName) {
                data.set('logo', this.newCompany.file, this.newCompany.fileName);
            }

            axios.post('/api/company', data)
                .then((response) => {
                    this.getCompanies();
                    this.newCompany = {};
                    message.success(response.data.message, 3);
                    this.isCreating = false;
                    this.showCreateModal = false;
                })
                .catch((error) => {
                    this.newCompany = {};
                    message.error(error.message, 3);
                });
        },

        handleLogoValidationCreate(file) {
            const isPNG = file.type === 'image/png';
            if (!isPNG) {
                message.error(`${file.name} is not a png file`, 3);
                this.logoErrorUploadingCreate = true;
            } else {
                this.logoErrorUploadingCreate = false;
            }

            return false;
        },

        handleLogoChangeCreate(file, fileList, event) {
            if (this.logoErrorUploadingCreate) {
                // remove unaccepted file
                this.newCompany.logo = [];
                this.newCompany.file = null;
                this.newCompany.fileName = null;
            } else {
                if (file && file.file) {
                    this.newCompany.file = file.file;
                    this.newCompany.fileName = file.file.name;
                }
            }
        },
    },
}
</script>

