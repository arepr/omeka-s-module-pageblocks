const initBlockSidebar = (id, updaters) => {
    const sidebar = $(`#${id}-sidebar`);
    var selectingElement;

    const addAttachment = function () {
        // Clear all form inputs when adding a new item
        selectingElement = $(this);
        sidebar.find("input, textarea").val("");
        sidebar.find("select").each(function () {
            const defaultVal = $(this).find("option").first().val();
            $(this).val(defaultVal);
        });
        sidebar.find(".asset-form-clear").click();
        openSidebar();
    };

    const editAttachment = function () {
        // Load saved values into the form inputs
        selectingElement = $(this);
        populateSidebarFields();
        openSidebar();
    };
    
    const openSidebar = function () {
        Omeka.openSidebar(sidebar);
    };

    const populateSidebarFields = function () {
        sidebar.find("input, textarea, select").each(function (index, elem) {
            elem = $(elem);
            
            const attachment = selectingElement.parents(".attachment");
            const input = attachment.find("input[data-sidebar-id=\"" + elem.attr("data-sidebar-id") + "\"]")
            elem.val(input.val());
            
            // Additional handling for saved assets
            if (input.is("[data-asset-url]")) {
                const asset = elem.parents(".asset-form-element");
                const url = input.attr("data-asset-url");
                const filename = input.attr("data-asset-filename");

                if (url && filename) {
                    asset.find(".selected-asset-image").attr("src", url);
                    asset.find(".selected-asset-name").text(filename);
        
                    asset.find(".no-selected-asset").hide();
                    asset.find(".selected-asset, .asset-form-clear").show();
                } else {
                    asset.find(".asset-form-clear").click();
                }
            }
        });
    }

    const saveAttachment = function () {
        Omeka.closeSidebar(sidebar);

        if (selectingElement.hasClass(`${id}-form-add`)) {
            // Create a new orderable list item on save
            insertAttachmentTemplate();
        }
        
        sidebar.find("input, textarea, select").each(function (index, elem) {
            elem = $(elem);
            
            // Sync the form values with the hidden inputs
            const attachment = selectingElement.parents(".attachment");
            const input = attachment.find("input[data-sidebar-id=\"" + elem.attr("data-sidebar-id") + "\"]");
            input.val(elem.val());

            // Additional handling for syncing assets
            if (input.is("[data-asset-url]")) {
                const asset = elem.parents(".asset-form-element");
                const hasValue = Boolean(input.val());

                const url = hasValue ? asset.find(".selected-asset-image").attr("src") : "";
                const filename = hasValue ? asset.find(".selected-asset-name").text() : "";

                input.attr("data-asset-url", url);
                input.attr("data-asset-filename", filename);
            }
            
            // Update the attachment previews based on block type
            const componentId = elem.attr("data-sidebar-id").replace(`${id}-data-`, "");
            updaters[componentId]?.(attachment, elem, index);
        });
    };

    const insertAttachmentTemplate = function () {
        const attachments = selectingElement.parents(".attachments");
        const newAttachment = $(attachments.data("template"));
        attachments.append(newAttachment);
        selectingElement = newAttachment.find(`.${id}-form-edit`);
    }

    const assetFormClearFix = function () {
        $(this).siblings(".no-selected-asset").show();
    };

    $("#content").on("click", `.${id}-form-add`, addAttachment);
    $("#content").on("click", `.${id}-form-edit`, editAttachment);
    $("#content").on("click", `#${id}-sidebar .confirm-panel > button`, saveAttachment);
    $("#content").on("click", `#${id}-sidebar .asset-form-clear`, assetFormClearFix);
};