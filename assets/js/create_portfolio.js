const nameInput = document.getElementById('nameInput');
const bioInput = document.getElementById('bioInput');
const typeInput = document.getElementById('typeInput');
const imageInput = document.getElementById('imageInput');

const previewName = document.getElementById('previewName');
const previewBio = document.getElementById('previewBio');
const previewType = document.getElementById('previewType');
const previewImage = document.getElementById('previewImage');

const educationSection = document.getElementById('educationSection');
const experienceSection = document.getElementById('experienceSection');
const previewEducationSection = document.getElementById('previewEducationSection');
const previewExperienceSection = document.getElementById('previewExperienceSection');

const portfolioForm = document.getElementById('portfolioForm');
const submitBtn = document.getElementById('submitBtn');

nameInput.addEventListener('input', () => {
    previewName.textContent = nameInput.value.trim() || 'Your Name';
});

bioInput.addEventListener('input', () => {
    previewBio.textContent = bioInput.value.trim() || 'Your bio will appear here...';
});

typeInput.addEventListener('change', () => {
    const val = typeInput.value;

    previewType.textContent = val ? val.charAt(0).toUpperCase() + val.slice(1) : 'Your Role Type';

    if(val === 'experienced'){
        educationSection.classList.add('hidden');
        experienceSection.classList.remove('hidden');

        previewEducationSection.classList.add('hidden');
        previewExperienceSection.classList.remove('hidden');
    } else {
        educationSection.classList.remove('hidden');
        experienceSection.classList.add('hidden');

        previewEducationSection.classList.remove('hidden');
        previewExperienceSection.classList.add('hidden');
    }

    refreshEducationPreview();
    refreshExperiencePreview();
});

imageInput.addEventListener('change', function(){
    const file = this.files[0];
    if(!file) return;

    const reader = new FileReader();
    reader.onload = function(e){
        previewImage.src = e.target.result;
    };
    reader.readAsDataURL(file);
});

function removeItem(button) {
    const item = button.closest('.removable-item');
    if (item) {
        item.remove();
        refreshSkillsPreview();
        refreshProjectsPreview();
        refreshEducationPreview();
        refreshExperiencePreview();
        refreshSocialPreview();
    }
}

function bindSkillPreview() {
    document.querySelectorAll('.skill-input').forEach(input => {
        input.removeEventListener('input', refreshSkillsPreview);
        input.addEventListener('input', refreshSkillsPreview);
    });
}

function refreshSkillsPreview() {
    const wrap = document.getElementById('previewSkills');
    const values = [...document.querySelectorAll('.skill-input')]
        .map(i => i.value.trim())
        .filter(v => v !== '');

    if(values.length === 0){
        wrap.innerHTML = '<span class="muted">No skills added yet</span>';
        return;
    }

    wrap.innerHTML = values.map(v => `<span class="skill-tag">${v}</span>`).join('');
}

function addSkill() {
    const container = document.getElementById('skillsContainer');
    container.insertAdjacentHTML('beforeend',
        `<div class="group removable-item">
            <input class="field skill-input" type="text" name="skills[]" placeholder="Skill">
            <button type="button" class="btn btn-secondary remove-btn" onclick="removeItem(this)">Remove</button>
        </div>`
    );
    bindSkillPreview();
}

function bindProjectPreview() {
    document.querySelectorAll('.project-title, .project-desc').forEach(input => {
        input.removeEventListener('input', refreshProjectsPreview);
        input.addEventListener('input', refreshProjectsPreview);
    });
}

function refreshProjectsPreview() {
    const wrap = document.getElementById('previewProjects');
    const titles = [...document.querySelectorAll('.project-title')];
    const descs = [...document.querySelectorAll('.project-desc')];

    let html = '';

    for(let i = 0; i < titles.length; i++){
        const title = titles[i].value.trim();
        const desc = descs[i].value.trim();

        if(title || desc){
            html += `
                <div class="project-item">
                    <strong>${title || 'Untitled Project'}</strong>
                    <div class="muted">${desc || ''}</div>
                </div>
            `;
        }
    }

    wrap.innerHTML = html || '<p class="muted">No projects added yet</p>';
}

function addProject() {
    document.getElementById('projectsContainer').insertAdjacentHTML('beforeend',
        `<div class="group removable-item project-group">
            <input class="field project-title" type="text" name="project_title[]" placeholder="Project Title">
            <textarea class="project-desc" name="project_desc[]" placeholder="Project Description"></textarea>
            <input class="field" type="text" name="project_link[]" placeholder="Project Link">
            <button type="button" class="btn btn-secondary remove-btn" onclick="removeItem(this)">Remove</button>
        </div>`
    );
    bindProjectPreview();
}

function bindEducationPreview() {
    document.querySelectorAll('.edu-degree, .edu-college, .edu-year').forEach(input => {
        input.removeEventListener('input', refreshEducationPreview);
        input.addEventListener('input', refreshEducationPreview);
    });
}

function refreshEducationPreview() {
    const wrap = document.getElementById('previewEducation');
    const degrees = [...document.querySelectorAll('.edu-degree')];
    const colleges = [...document.querySelectorAll('.edu-college')];
    const years = [...document.querySelectorAll('.edu-year')];

    let html = '';

    for(let i = 0; i < degrees.length; i++){
        const degree = degrees[i].value.trim();
        const college = colleges[i].value.trim();
        const year = years[i].value.trim();

        if(degree || college || year){
            html += `
                <div class="edu-item">
                    <strong>${degree || 'Degree'}</strong>
                    <div class="muted">${college || ''}</div>
                    <div class="muted">${year || ''}</div>
                </div>
            `;
        }
    }

    wrap.innerHTML = html || '<p class="muted">No education details yet</p>';
}

function addEducation() {
    document.getElementById('educationContainer').insertAdjacentHTML('beforeend',
        `<div class="group removable-item edu-group">
            <input class="field edu-degree" type="text" name="degree[]" placeholder="Degree">
            <input class="field edu-college" type="text" name="college[]" placeholder="College">
            <input class="field edu-year" type="text" name="year[]" placeholder="Year">
            <input class="field" type="text" name="percentage[]" placeholder="Percentage / CGPA">
            <button type="button" class="btn btn-secondary remove-btn" onclick="removeItem(this)">Remove</button>
        </div>`
    );
    bindEducationPreview();
}

function bindExperiencePreview() {
    document.querySelectorAll('.exp-company, .exp-role, .exp-desc').forEach(input => {
        input.removeEventListener('input', refreshExperiencePreview);
        input.addEventListener('input', refreshExperiencePreview);
    });
}

function refreshExperiencePreview() {
    const wrap = document.getElementById('previewExperience');
    const companies = [...document.querySelectorAll('.exp-company')];
    const roles = [...document.querySelectorAll('.exp-role')];
    const descs = [...document.querySelectorAll('.exp-desc')];

    let html = '';

    for(let i = 0; i < companies.length; i++){
        const company = companies[i].value.trim();
        const role = roles[i].value.trim();
        const desc = descs[i].value.trim();

        if(company || role || desc){
            html += `
                <div class="exp-item">
                    <strong>${role || 'Role'}</strong>
                    <div class="muted">${company || ''}</div>
                    <div class="muted">${desc || ''}</div>
                </div>
            `;
        }
    }

    wrap.innerHTML = html || '<p class="muted">No experience details yet</p>';
}

function addExperience() {
    document.getElementById('experienceContainer').insertAdjacentHTML('beforeend',
        `<div class="group removable-item exp-group">
            <input class="field exp-company" type="text" name="company[]" placeholder="Company">
            <input class="field exp-role" type="text" name="role[]" placeholder="Role">
            <div class="row">
                <input class="field" type="date" name="start_date[]">
                <input class="field" type="date" name="end_date[]">
            </div>
            <textarea class="exp-desc" name="experience_desc[]" placeholder="Description"></textarea>
            <button type="button" class="btn btn-secondary remove-btn" onclick="removeItem(this)">Remove</button>
        </div>`
    );
    bindExperiencePreview();
}

function bindSocialPreview() {
    document.querySelectorAll('.social-group input').forEach(input => {
        input.removeEventListener('input', refreshSocialPreview);
        input.addEventListener('input', refreshSocialPreview);
    });
}

function refreshSocialPreview() {
    const wrap = document.getElementById('previewSocialLinks');
    const groups = [...document.querySelectorAll('.social-group')];
    let html = '';

    groups.forEach(group => {
        const platform = group.querySelector('input[name="social_platform[]"]')?.value.trim() || '';
        const url = group.querySelector('input[name="social_url[]"]')?.value.trim() || '';

        if (platform && url) {
            html += `
                <div class="social-item">
                    <strong>${platform}</strong><br>
                    <a href="${url}" target="_blank">${url}</a>
                </div>
            `;
        }
    });

    wrap.innerHTML = html || '<p class="muted">No social links added yet</p>';
}

function addSocial() {
    document.getElementById('socialContainer').insertAdjacentHTML('beforeend',
        `<div class="group removable-item social-group">
            <div class="row">
                <input class="field" type="text" name="social_platform[]" placeholder="Platform (GitHub, LinkedIn)">
                <input class="field social-url" type="url" name="social_url[]" placeholder="https://..." pattern="https?://.+" title="Please enter a valid URL starting with http:// or https://">
            </div>
            <button type="button" class="btn btn-secondary remove-btn" onclick="removeItem(this)">Remove</button>
        </div>`
    );
    bindSocialPreview();
}

portfolioForm.addEventListener('submit', function () {
    submitBtn.disabled = true;
    submitBtn.classList.add('saving');
    submitBtn.textContent = 'Saving...';
});

bindSkillPreview();
bindProjectPreview();
bindEducationPreview();
bindExperiencePreview();
bindSocialPreview();
function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function importGithub() {
    const usernameInput = document.querySelector('input[name="github_username"]');
    const username = usernameInput.value.trim();

    if (!username) {
        alert("Enter GitHub username first!");
        return;
    }

    fetch(`/portfolio-builder/api/github.php?username=${encodeURIComponent(username)}`)
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }

            if (!Array.isArray(data) || data.length === 0) {
                alert("No repositories found.");
                return;
            }

            const container = document.getElementById('projectsContainer');
            container.innerHTML = '';

            data.slice(0, 5).forEach(repo => {
                const repoName = escapeHtml(repo.name || '');
                const repoDesc = escapeHtml(repo.description || '');
                const repoUrl = escapeHtml(repo.url || '');

                container.insertAdjacentHTML('beforeend', `
                    <div class="group removable-item project-group">
                        <input class="field project-title" type="text" name="project_title[]" value="${repoName}">
                        <textarea class="project-desc" name="project_desc[]">${repoDesc}</textarea>
                        <input class="field" type="text" name="project_link[]" value="${repoUrl}">
                        <button type="button" class="btn btn-secondary remove-btn" onclick="removeItem(this)">Remove</button>
                    </div>
                `);
            });

            bindProjectPreview();
            refreshProjectsPreview();
        })
        .catch(err => {
            console.error(err);
            alert("Error fetching GitHub data");
        });
}
function improveBioWithAI() {
    const bioField = document.getElementById('bioInput');
    const button = document.getElementById('improveBioBtn');
    const status = document.getElementById('aiBioStatus');
    const style = document.getElementById('bioStyle').value;

    const bio = bioField.value.trim();

    if (!bio) {
        alert('Please write a bio first.');
        return;
    }

    button.disabled = true;
    button.classList.add('saving');
    button.textContent = 'Improving...';
    status.textContent = 'Improving bio...';

    fetch('/portfolio-builder/api/ai.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ bio, style })
    })
    .then(res => res.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
            status.textContent = '';
            return;
        }

        if (data.success && data.bio) {
            bioField.value = data.bio;
            previewBio.textContent = data.bio;
            status.textContent = `Bio improved (${data.style} mode).`;
        } else {
            status.textContent = '';
            alert('Could not improve bio.');
        }
    })
    .catch(err => {
        console.error(err);
        status.textContent = '';
        alert('Something went wrong while improving the bio.');
    })
    .finally(() => {
        button.disabled = false;
        button.classList.remove('saving');
        button.textContent = 'Improve Bio with AI';
    });
}