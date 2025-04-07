class UploadService {
    static appendFormData($form, formData) {
        new FormData($form[0]).forEach((value, key) => {
            if (!formData.has(key)) formData.append(key, value);
        });
    }

    static handleSuccess(file, response, dropzone) {
        if (typeof response === "object") {
            if (!response.success) {
                const fileStatus = response.files[file.name];
                if (fileStatus?.status === "error") {
                    file.status = Dropzone.ERROR
                    UploadUI.addErrors(file, fileStatus.errors);
                    $(file.previewElement)
                        .find("[data-dz-uploadprogress]")
                        .removeClass("bg-success")
                        .addClass("bg-danger");
                }
            } else {
                dropzone.removeFile(file);
            }
        }
    }

    static handleError(file, response, xhr) {
        $(file.previewElement)
            .find("[data-dz-uploadprogress]")
            .removeClass("bg-success")
            .addClass("bg-danger");

        let errorMessage = "Не удалось сохранить файл.";
        if (typeof response === "object" && response) {
            const fileStatus = response.files[file.name] || response.files["global"];
            if (fileStatus?.errors) {
                UploadUI.addErrors(file, fileStatus.errors);
                return;
            }
        } else if (xhr) {
            errorMessage = xhr.status === 413
                ? "Файл превышает допустимый размер."
                : `${xhr.status}: ${xhr.statusText}`;
        } else if (typeof response === "string") {
            errorMessage = response;
        }
        UploadUI.addError(file, errorMessage);
    }
}