<template>
  <AuthenticatedLayout>
    <div class="flex min-h-screen bg-zinc-50">
      <!-- SIDEBAR SLOT SPACE -->
      <Hrsidebar />

      <!-- MAIN CONTENT CONTAINER -->
      <div class="flex-1 p-6 text-zinc-900 font-sans min-w-0">
        
        <!-- TOP BAR -->
        <header class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
          <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-indigo-600 uppercase tracking-wider mb-1">
              <span class="w-2 h-2 rounded-full bg-indigo-600 animate-pulse"></span>
              Organization Structure
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-950">Organizational Chart</h1>
            <p class="text-sm text-zinc-500">Interactive visual hierarchy of your organization. Explore reporting lines, teams, and departmental structures.</p>
          </div>

          <!-- Toolbar Actions -->
          <div class="flex flex-wrap items-center gap-2">
            <!-- View Toggle -->
            <div class="flex items-center bg-white border border-zinc-200 rounded-lg p-1">
              <button 
                @click="viewMode = 'hierarchy'"
                :class="viewMode === 'hierarchy' ? 'bg-indigo-600 text-white' : 'text-zinc-600 hover:bg-zinc-50'"
                class="px-3 py-1.5 rounded text-xs font-semibold transition-colors"
              >
                <span class="inline-flex items-center gap-1.5"><Building2 class="w-3.5 h-3.5" />Hierarchy</span>
              </button>
              <button 
                @click="viewMode = 'department'"
                :class="viewMode === 'department' ? 'bg-indigo-600 text-white' : 'text-zinc-600 hover:bg-zinc-50'"
                class="px-3 py-1.5 rounded text-xs font-semibold transition-colors"
              >
                <span class="inline-flex items-center gap-1.5"><BarChart3 class="w-3.5 h-3.5" />Department</span>
              </button>
            </div>

            <!-- Filters -->
            <select v-model="filters.branch" class="bg-white border border-zinc-200 rounded-lg px-3 py-2 text-xs text-zinc-600 focus:ring-2 focus:ring-indigo-500">
              <option value="">All Branches</option>
              <option value="HQ">Headquarters</option>
              <option value="Asia">Asia-Pacific</option>
            </select>
            <select v-model="filters.department" class="bg-white border border-zinc-200 rounded-lg px-3 py-2 text-xs text-zinc-600 focus:ring-2 focus:ring-indigo-500">
              <option value="">All Departments</option>
              <option value="Engineering">Engineering</option>
              <option value="Sales">Sales & Marketing</option>
              <option value="HR">Human Resources</option>
            </select>

            <!-- Search -->
            <div class="relative">
              <span class="absolute inset-y-0 left-0 flex items-center pl-3"><Search class="w-4 h-4 text-zinc-400" /></span>
              <input 
                v-model="searchQuery"
                type="text" 
                placeholder="Search employee or position..." 
                class="w-56 pl-9 pr-4 py-2 bg-white border border-zinc-200 rounded-lg text-xs focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              />
            </div>

            <!-- Actions -->
            <button @click="zoomIn" class="p-2 bg-white border border-zinc-200 hover:bg-zinc-50 rounded-lg text-zinc-600 transition-colors" title="Zoom In">
              <ZoomIn class="w-4 h-4" />
            </button>
            <button @click="zoomOut" class="p-2 bg-white border border-zinc-200 hover:bg-zinc-50 rounded-lg text-zinc-600 transition-colors" title="Zoom Out">
              <ZoomOut class="w-4 h-4" />
            </button>
            <button @click="resetZoom" class="p-2 bg-white border border-zinc-200 hover:bg-zinc-50 rounded-lg text-zinc-600 transition-colors" title="Reset View">
              <RefreshCw class="w-4 h-4" />
            </button>
            <button @click="printChart" class="px-3 py-2 bg-white border border-zinc-200 hover:bg-zinc-50 text-zinc-700 rounded-lg text-xs font-semibold transition-colors shadow-sm">
              <span class="inline-flex items-center gap-1.5"><Printer class="w-3.5 h-3.5" />Print</span>
            </button>
            <button @click="exportChart('pdf')" class="px-3 py-2 bg-white border border-zinc-200 hover:bg-zinc-50 text-zinc-700 rounded-lg text-xs font-semibold transition-colors shadow-sm">
              <span class="inline-flex items-center gap-1.5"><FileText class="w-3.5 h-3.5" />PDF</span>
            </button>
            <button @click="exportChart('image')" class="px-3 py-2 bg-white border border-zinc-200 hover:bg-zinc-50 text-zinc-700 rounded-lg text-xs font-semibold transition-colors shadow-sm">
              <span class="inline-flex items-center gap-1.5"><Image class="w-3.5 h-3.5" />Image</span>
            </button>
          </div>
        </header>

        <!-- WORKFORCE STATISTICS -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
          <div class="bg-white border border-zinc-200 rounded-xl p-4 text-center shadow-sm">
            <span class="text-2xl font-bold text-zinc-950">{{ totalEmployees }}</span>
            <p class="text-[10px] text-zinc-500 uppercase tracking-wider mt-1">Total Employees</p>
          </div>
          <div class="bg-white border border-zinc-200 rounded-xl p-4 text-center shadow-sm">
            <span class="text-2xl font-bold text-zinc-950">{{ totalDepartments }}</span>
            <p class="text-[10px] text-zinc-500 uppercase tracking-wider mt-1">Departments</p>
          </div>
          <div class="bg-white border border-zinc-200 rounded-xl p-4 text-center shadow-sm">
            <span class="text-2xl font-bold text-zinc-950">{{ totalPositions }}</span>
            <p class="text-[10px] text-zinc-500 uppercase tracking-wider mt-1">Positions</p>
          </div>
          <div class="bg-white border border-zinc-200 rounded-xl p-4 text-center shadow-sm">
            <span class="text-2xl font-bold text-amber-600">{{ totalVacancies }}</span>
            <p class="text-[10px] text-zinc-500 uppercase tracking-wider mt-1">Vacancies</p>
          </div>
          <div class="bg-white border border-zinc-200 rounded-xl p-4 text-center shadow-sm">
            <span class="text-2xl font-bold text-indigo-600">{{ avgSpanOfControl }}</span>
            <p class="text-[10px] text-zinc-500 uppercase tracking-wider mt-1">Avg Span of Control</p>
          </div>
        </div>

        <!-- LEGEND -->
        <div class="flex items-center gap-4 mb-4 p-3 bg-white border border-zinc-200 rounded-lg">
          <span class="text-xs font-semibold text-zinc-500">Legend:</span>
          <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded bg-emerald-500"></span>
            <span class="text-[10px] text-zinc-600">Filled Position</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded bg-amber-400 border-2 border-dashed border-amber-600"></span>
            <span class="text-[10px] text-zinc-600">Vacant Position</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded bg-zinc-300"></span>
            <span class="text-[10px] text-zinc-600">Inactive/Archived</span>
          </div>
        </div>

        <!-- ORGANIZATIONAL CHART CONTAINER -->
        <div class="bg-white border border-zinc-200 rounded-xl shadow-sm overflow-hidden">
          
          <!-- Chart Header -->
          <div class="p-4 border-b border-zinc-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <button 
                @click="toggleAllNodes"
                class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-semibold transition-colors"
              >
                <span v-if="allExpanded" class="inline-flex items-center gap-1.5"><FolderOpen class="w-3.5 h-3.5" />Collapse All</span>
                <span v-else class="inline-flex items-center gap-1.5"><Folder class="w-3.5 h-3.5" />Expand All</span>
              </button>
              <button 
                @click="highlightVacant = !highlightVacant"
                :class="highlightVacant ? 'bg-amber-100 text-amber-800 border-amber-300' : 'bg-zinc-50 text-zinc-600 border-zinc-200'"
                class="px-3 py-1.5 border rounded-lg text-xs font-semibold transition-colors"
              >
                <span class="inline-flex items-center gap-1.5"><Target class="w-3.5 h-3.5" />Highlight Vacancies</span>
              </button>
            </div>
            <span class="text-[10px] text-zinc-400">Zoom: {{ zoomLevel }}%</span>
          </div>

          <!-- Chart Canvas -->
          <div 
            class="p-8 overflow-auto"
            :style="{ transform: `scale(${zoomLevel / 100})`, transformOrigin: 'top center' }"
          >
            <div class="min-w-[800px] flex justify-center">
              
              <!-- CEO Level -->
              <div class="flex flex-col items-center">
                
                <!-- CEO Node -->
                <div 
                  @click="selectNode(orgData.ceo)"
                  :class="[
                    'relative bg-white border-2 rounded-xl p-4 cursor-pointer transition-all hover:shadow-xl min-w-[200px]',
                    selectedNode?.id === orgData.ceo.id ? 'border-indigo-500 shadow-lg ring-2 ring-indigo-200' : 'border-indigo-200 shadow-sm hover:border-indigo-400'
                  ]"
                >
                  <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-lg">
                      {{ orgData.ceo.avatar }}
                    </div>
                    <div>
                      <h4 class="text-sm font-bold text-zinc-950">{{ orgData.ceo.name }}</h4>
                      <p class="text-[10px] text-indigo-600 font-semibold">{{ orgData.ceo.position }}</p>
                      <p class="text-[9px] text-zinc-400">{{ orgData.ceo.department }}</p>
                    </div>
                  </div>
                  <!-- Span of Control Badge -->
                  <span class="absolute -top-2 -right-2 bg-indigo-600 text-white text-[9px] font-bold px-2 py-0.5 rounded-full">
                    {{ orgData.ceo.subordinates?.length || 0 }}
                  </span>
                </div>

                <!-- Connector Line -->
                <div class="w-px h-8 bg-zinc-300"></div>
                <div class="w-full max-w-4xl h-px bg-zinc-300"></div>

                <!-- C-Level Executives Row -->
                <div class="flex gap-8 mt-8">
                  
                  <!-- CFO Branch -->
                  <div v-if="orgData.cfo" class="flex flex-col items-center">
                    <div class="w-px h-8 bg-zinc-300"></div>
                    
                    <div 
                      @click="selectNode(orgData.cfo)"
                      :class="[
                        'relative bg-white border-2 rounded-xl p-4 cursor-pointer transition-all hover:shadow-xl min-w-[180px]',
                        selectedNode?.id === orgData.cfo.id ? 'border-indigo-500 shadow-lg ring-2 ring-indigo-200' : 'border-emerald-200 shadow-sm hover:border-emerald-400'
                      ]"
                    >
                      <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white font-bold">
                          {{ orgData.cfo.avatar }}
                        </div>
                        <div>
                          <h4 class="text-xs font-bold text-zinc-950">{{ orgData.cfo.name }}</h4>
                          <p class="text-[9px] text-emerald-600 font-semibold">{{ orgData.cfo.position }}</p>
                        </div>
                      </div>
                      <button 
                        v-if="orgData.cfo.subordinates?.length"
                        @click.stop="toggleNode(orgData.cfo.id)"
                        class="absolute -bottom-2 left-1/2 -translate-x-1/2 bg-white border border-zinc-200 rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-zinc-50 transition-colors"
                      >
                        {{ expandedNodes[orgData.cfo.id] ? '−' : '+' }}
                      </button>
                    </div>

                    <!-- CFO Subordinates -->
                    <div v-if="expandedNodes[orgData.cfo.id] && orgData.cfo.subordinates" class="mt-8">
                      <div class="flex gap-6">
                        <div v-for="sub in orgData.cfo.subordinates" :key="sub.id" class="flex flex-col items-center">
                          <div class="w-px h-8 bg-zinc-300"></div>
                          
                          <div 
                            @click="selectNode(sub)"
                            :class="[
                              'relative bg-white border-2 rounded-xl p-3 cursor-pointer transition-all hover:shadow-lg min-w-[160px]',
                              highlightVacant && sub.status === 'vacant' ? 'border-amber-400 border-dashed bg-amber-50' : '',
                              selectedNode?.id === sub.id ? 'border-indigo-500 shadow-lg ring-2 ring-indigo-200' : 'border-zinc-200 shadow-sm hover:border-indigo-300'
                            ]"
                          >
                            <div class="flex items-center gap-2">
                              <div :class="[
                                'w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-xs',
                                sub.status === 'vacant' ? 'bg-amber-400' : 'bg-gradient-to-br from-blue-500 to-blue-600'
                              ]">
                                {{ sub.avatar || '?' }}
                              </div>
                              <div>
                                <h4 class="text-[11px] font-bold text-zinc-950">{{ sub.name }}</h4>
                                <p class="text-[9px] text-indigo-600 font-semibold">{{ sub.position }}</p>
                                <p v-if="sub.status === 'vacant'" class="text-[8px] text-amber-600 font-bold"><span class="inline-flex items-center gap-0.5"><AlertTriangle class="w-2.5 h-2.5" />Vacant</span></p>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- COO Branch -->
                  <div v-if="orgData.coo" class="flex flex-col items-center">
                    <div class="w-px h-8 bg-zinc-300"></div>
                    
                    <div 
                      @click="selectNode(orgData.coo)"
                      :class="[
                        'relative bg-white border-2 rounded-xl p-4 cursor-pointer transition-all hover:shadow-xl min-w-[180px]',
                        selectedNode?.id === orgData.coo.id ? 'border-indigo-500 shadow-lg ring-2 ring-indigo-200' : 'border-purple-200 shadow-sm hover:border-purple-400'
                      ]"
                    >
                      <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center text-white font-bold">
                          {{ orgData.coo.avatar }}
                        </div>
                        <div>
                          <h4 class="text-xs font-bold text-zinc-950">{{ orgData.coo.name }}</h4>
                          <p class="text-[9px] text-purple-600 font-semibold">{{ orgData.coo.position }}</p>
                        </div>
                      </div>
                      <button 
                        v-if="orgData.coo.subordinates?.length"
                        @click.stop="toggleNode(orgData.coo.id)"
                        class="absolute -bottom-2 left-1/2 -translate-x-1/2 bg-white border border-zinc-200 rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-zinc-50 transition-colors"
                      >
                        {{ expandedNodes[orgData.coo.id] ? '−' : '+' }}
                      </button>
                    </div>

                    <!-- COO Subordinates -->
                    <div v-if="expandedNodes[orgData.coo.id] && orgData.coo.subordinates" class="mt-8">
                      <div class="flex gap-6">
                        <div v-for="sub in orgData.coo.subordinates" :key="sub.id" class="flex flex-col items-center">
                          <div class="w-px h-8 bg-zinc-300"></div>
                          
                          <div 
                            @click="selectNode(sub)"
                            :class="[
                              'relative bg-white border-2 rounded-xl p-3 cursor-pointer transition-all hover:shadow-lg min-w-[160px]',
                              highlightVacant && sub.status === 'vacant' ? 'border-amber-400 border-dashed bg-amber-50' : '',
                              selectedNode?.id === sub.id ? 'border-indigo-500 shadow-lg ring-2 ring-indigo-200' : 'border-zinc-200 shadow-sm hover:border-indigo-300'
                            ]"
                          >
                            <div class="flex items-center gap-2">
                              <div :class="[
                                'w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-xs',
                                sub.status === 'vacant' ? 'bg-amber-400' : 'bg-gradient-to-br from-blue-500 to-blue-600'
                              ]">
                                {{ sub.avatar || '?' }}
                              </div>
                              <div>
                                <h4 class="text-[11px] font-bold text-zinc-950">{{ sub.name }}</h4>
                                <p class="text-[9px] text-indigo-600 font-semibold">{{ sub.position }}</p>
                                <p v-if="sub.status === 'vacant'" class="text-[8px] text-amber-600 font-bold"><span class="inline-flex items-center gap-0.5"><AlertTriangle class="w-2.5 h-2.5" />Vacant</span></p>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- HR Manager Branch -->
                  <div v-if="orgData.hrManager" class="flex flex-col items-center">
                    <div class="w-px h-8 bg-zinc-300"></div>
                    
                    <div 
                      @click="selectNode(orgData.hrManager)"
                      :class="[
                        'relative bg-white border-2 rounded-xl p-4 cursor-pointer transition-all hover:shadow-xl min-w-[180px]',
                        selectedNode?.id === orgData.hrManager.id ? 'border-indigo-500 shadow-lg ring-2 ring-indigo-200' : 'border-rose-200 shadow-sm hover:border-rose-400'
                      ]"
                    >
                      <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-rose-500 to-red-600 flex items-center justify-center text-white font-bold">
                          {{ orgData.hrManager.avatar }}
                        </div>
                        <div>
                          <h4 class="text-xs font-bold text-zinc-950">{{ orgData.hrManager.name }}</h4>
                          <p class="text-[9px] text-rose-600 font-semibold">{{ orgData.hrManager.position }}</p>
                        </div>
                      </div>
                      <button 
                        v-if="orgData.hrManager.subordinates?.length"
                        @click.stop="toggleNode(orgData.hrManager.id)"
                        class="absolute -bottom-2 left-1/2 -translate-x-1/2 bg-white border border-zinc-200 rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-zinc-50 transition-colors"
                      >
                        {{ expandedNodes[orgData.hrManager.id] ? '−' : '+' }}
                      </button>
                    </div>

                    <!-- HR Manager Subordinates -->
                    <div v-if="expandedNodes[orgData.hrManager.id] && orgData.hrManager.subordinates" class="mt-8">
                      <div class="flex gap-6">
                        <div v-for="sub in orgData.hrManager.subordinates" :key="sub.id" class="flex flex-col items-center">
                          <div class="w-px h-8 bg-zinc-300"></div>
                          
                          <div 
                            @click="selectNode(sub)"
                            :class="[
                              'relative bg-white border-2 rounded-xl p-3 cursor-pointer transition-all hover:shadow-lg min-w-[160px]',
                              highlightVacant && sub.status === 'vacant' ? 'border-amber-400 border-dashed bg-amber-50' : '',
                              selectedNode?.id === sub.id ? 'border-indigo-500 shadow-lg ring-2 ring-indigo-200' : 'border-zinc-200 shadow-sm hover:border-indigo-300'
                            ]"
                          >
                            <div class="flex items-center gap-2">
                              <div :class="[
                                'w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-xs',
                                sub.status === 'vacant' ? 'bg-amber-400' : 'bg-gradient-to-br from-blue-500 to-blue-600'
                              ]">
                                {{ sub.avatar || '?' }}
                              </div>
                              <div>
                                <h4 class="text-[11px] font-bold text-zinc-950">{{ sub.name }}</h4>
                                <p class="text-[9px] text-indigo-600 font-semibold">{{ sub.position }}</p>
                                <p v-if="sub.status === 'vacant'" class="text-[8px] text-amber-600 font-bold"><span class="inline-flex items-center gap-0.5"><AlertTriangle class="w-2.5 h-2.5" />Vacant</span></p>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                </div>
              </div>

            </div>
          </div>
        </div>

        <!-- POSITION DETAIL PANEL (Slide-in when node selected) -->
        <div 
          v-if="selectedNode"
          class="fixed right-0 top-0 h-full w-96 bg-white border-l border-zinc-200 shadow-2xl z-40 transform transition-transform overflow-y-auto"
        >
          <!-- Panel Header -->
          <div class="p-5 border-b border-zinc-100 flex items-center justify-between sticky top-0 bg-white">
            <h3 class="text-sm font-bold text-zinc-950">Position Details</h3>
            <button @click="selectedNode = null" class="p-1.5 hover:bg-zinc-100 rounded-lg text-zinc-400 hover:text-zinc-600 transition-colors">
              <X class="w-4 h-4" />
            </button>
          </div>

          <!-- Panel Content -->
          <div class="p-5 space-y-5">
            <!-- Position Header -->
            <div class="text-center">
              <div class="w-20 h-20 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-2xl mx-auto mb-3">
                {{ selectedNode.avatar || '?' }}
              </div>
              <h2 class="text-lg font-bold text-zinc-950">{{ selectedNode.name }}</h2>
              <p class="text-sm text-indigo-600 font-semibold">{{ selectedNode.position }}</p>
              <p class="text-xs text-zinc-500">{{ selectedNode.department }}</p>
              
              <span v-if="selectedNode.status === 'vacant'" class="inline-flex items-center gap-1 mt-2 px-3 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-full text-xs font-semibold">
                <AlertTriangle class="w-3.5 h-3.5" />Vacant Position
              </span>
              <span v-else class="inline-flex items-center gap-1 mt-2 px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-xs font-semibold">
                <CheckCircle2 class="w-3.5 h-3.5" />Filled
              </span>
            </div>

            <!-- Job Description -->
            <div class="bg-zinc-50 border border-zinc-200 rounded-lg p-4">
              <h4 class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Job Description</h4>
              <p class="text-xs text-zinc-700 leading-relaxed">{{ selectedNode.jobDescription || 'No description available.' }}</p>
            </div>

            <!-- Assigned Employees -->
            <div v-if="selectedNode.employees?.length">
              <h4 class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Assigned Employees</h4>
              <div class="space-y-2">
                <div v-for="emp in selectedNode.employees" :key="emp.id" class="flex items-center gap-3 p-2 bg-white border border-zinc-200 rounded-lg">
                  <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-xs">
                    {{ emp.avatar }}
                  </div>
                  <div>
                    <p class="text-xs font-semibold text-zinc-800">{{ emp.name }}</p>
                    <p class="text-[10px] text-zinc-500">{{ emp.email }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Supervisor -->
            <div v-if="selectedNode.supervisor" class="bg-zinc-50 border border-zinc-200 rounded-lg p-4">
              <h4 class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Reports To</h4>
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 font-bold text-xs">
                  {{ selectedNode.supervisor.avatar }}
                </div>
                <div>
                  <p class="text-xs font-semibold text-zinc-800">{{ selectedNode.supervisor.name }}</p>
                  <p class="text-[10px] text-zinc-500">{{ selectedNode.supervisor.position }}</p>
                </div>
              </div>
            </div>

            <!-- Subordinates -->
            <div v-if="selectedNode.subordinates?.length">
              <h4 class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">
                Subordinates ({{ selectedNode.subordinates.length }})
              </h4>
              <div class="space-y-2">
                <div v-for="sub in selectedNode.subordinates" :key="sub.id" class="flex items-center gap-3 p-2 bg-white border border-zinc-200 rounded-lg">
                  <div :class="[
                    'w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs',
                    sub.status === 'vacant' ? 'bg-amber-100 text-amber-600' : 'bg-emerald-100 text-emerald-600'
                  ]">
                    {{ sub.avatar || '?' }}
                  </div>
                  <div>
                    <p class="text-xs font-semibold text-zinc-800">{{ sub.name }}</p>
                    <p class="text-[10px] text-zinc-500">{{ sub.position }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Span of Control -->
            <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
              <h4 class="text-xs font-bold text-indigo-600 uppercase tracking-wider mb-2">Span of Control</h4>
              <div class="text-2xl font-bold text-indigo-700">{{ selectedNode.subordinates?.length || 0 }}</div>
              <p class="text-[10px] text-indigo-500 mt-1">Direct reports</p>
            </div>
          </div>
        </div>

        <!-- Overlay when panel is open -->
        <div v-if="selectedNode" @click="selectedNode = null" class="fixed inset-0 bg-black/20 z-30"></div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Building2, BarChart3, Search, ZoomIn, ZoomOut, RefreshCw, Printer, FileText, Image, FolderOpen, Folder, Target, AlertTriangle, X, CheckCircle2 } from 'lucide-vue-next';

// State
const viewMode = ref('hierarchy');
const zoomLevel = ref(100);
const allExpanded = ref(true);
const highlightVacant = ref(false);
const selectedNode = ref(null);
const searchQuery = ref('');

const filters = ref({
  branch: '',
  department: ''
});

const expandedNodes = ref({
  cfo: true,
  coo: true,
  hr_manager: true
});

// Organizational Data
const orgData = ref({
  ceo: {
    id: 'ceo',
    name: 'Alexander Mitchell',
    avatar: 'AM',
    position: 'Chief Executive Officer',
    department: 'Executive Office',
    status: 'filled',
    jobDescription: 'Lead the organization, set strategic direction, and oversee all operations.',
    supervisor: null,
    employees: [
      { id: 1, name: 'Alexander Mitchell', email: 'a.mitchell@nexus.com', avatar: 'AM' }
    ],
    subordinates: ['cfo', 'coo', 'hr_manager']
  },
  cfo: {
    id: 'cfo',
    name: 'Victoria Chang',
    avatar: 'VC',
    position: 'Chief Financial Officer',
    department: 'Finance',
    status: 'filled',
    jobDescription: 'Oversee financial operations, budgeting, and fiscal strategy.',
    supervisor: { name: 'Alexander Mitchell', position: 'CEO', avatar: 'AM' },
    employees: [
      { id: 2, name: 'Victoria Chang', email: 'v.chang@nexus.com', avatar: 'VC' }
    ],
    subordinates: [
      {
        id: 'finance_mgr',
        name: 'David Kumar',
        avatar: 'DK',
        position: 'Finance Manager',
        department: 'Finance',
        status: 'filled',
        employees: [
          { id: 3, name: 'David Kumar', email: 'd.kumar@nexus.com', avatar: 'DK' }
        ],
        subordinates: [
          {
            id: 'accountant_1',
            name: 'Lisa Chen',
            avatar: 'LC',
            position: 'Senior Accountant',
            department: 'Finance',
            status: 'filled',
            employees: [
              { id: 4, name: 'Lisa Chen', email: 'l.chen@nexus.com', avatar: 'LC' }
            ]
          },
          {
            id: 'accountant_2',
            name: 'Position Vacant',
            avatar: '?',
            position: 'Accounting Assistant',
            department: 'Finance',
            status: 'vacant',
            employees: []
          }
        ]
      }
    ]
  },
  coo: {
    id: 'coo',
    name: 'Marcus Rodriguez',
    avatar: 'MR',
    position: 'Chief Operations Officer',
    department: 'Operations',
    status: 'filled',
    jobDescription: 'Manage daily operations, process optimization, and service delivery.',
    supervisor: { name: 'Alexander Mitchell', position: 'CEO', avatar: 'AM' },
    employees: [
      { id: 5, name: 'Marcus Rodriguez', email: 'm.rodriguez@nexus.com', avatar: 'MR' }
    ],
    subordinates: [
      {
        id: 'ops_mgr',
        name: 'Sarah Thompson',
        avatar: 'ST',
        position: 'Operations Manager',
        department: 'Operations',
        status: 'filled',
        employees: [
          { id: 6, name: 'Sarah Thompson', email: 's.thompson@nexus.com', avatar: 'ST' }
        ]
      },
      {
        id: 'logistics_mgr',
        name: 'James Wilson',
        avatar: 'JW',
        position: 'Logistics Manager',
        department: 'Logistics',
        status: 'filled',
        employees: [
          { id: 7, name: 'James Wilson', email: 'j.wilson@nexus.com', avatar: 'JW' }
        ]
      }
    ]
  },
  hrManager: {
    id: 'hr_manager',
    name: 'Emily Parker',
    avatar: 'EP',
    position: 'HR Manager',
    department: 'Human Resources',
    status: 'filled',
    jobDescription: 'Lead HR operations, talent management, and organizational development.',
    supervisor: { name: 'Alexander Mitchell', position: 'CEO', avatar: 'AM' },
    employees: [
      { id: 8, name: 'Emily Parker', email: 'e.parker@nexus.com', avatar: 'EP' }
    ],
    subordinates: [
      {
        id: 'hr_specialist',
        name: 'Rachel Gomez',
        avatar: 'RG',
        position: 'HR Specialist',
        department: 'Human Resources',
        status: 'filled',
        employees: [
          { id: 9, name: 'Rachel Gomez', email: 'r.gomez@nexus.com', avatar: 'RG' }
        ]
      },
      {
        id: 'recruiter',
        name: 'Position Vacant',
        avatar: '?',
        position: 'Recruiter',
        department: 'Human Resources',
        status: 'vacant',
        employees: []
      }
    ]
  }
});

// Computed
const totalEmployees = computed(() => 9); // Simplified count
const totalDepartments = computed(() => 5);
const totalPositions = computed(() => 12);
const totalVacancies = computed(() => 3);
const avgSpanOfControl = computed(() => 3.2);

// Functions
const toggleNode = (id) => {
  expandedNodes.value[id] = !expandedNodes.value[id];
};

const toggleAllNodes = () => {
  allExpanded.value = !allExpanded.value;
  Object.keys(expandedNodes.value).forEach(key => {
    expandedNodes.value[key] = allExpanded.value;
  });
};

const selectNode = (node) => {
  selectedNode.value = node;
};

const zoomIn = () => {
  zoomLevel.value = Math.min(zoomLevel.value + 10, 200);
};

const zoomOut = () => {
  zoomLevel.value = Math.max(zoomLevel.value - 10, 50);
};

const resetZoom = () => {
  zoomLevel.value = 100;
};

const printChart = () => {
  window.print();
};

const exportChart = (format) => {
  alert(`Exporting organizational chart as ${format.toUpperCase()}...`);
};
</script>

<style scoped>
@media print {
  .fixed, button, select, input {
    display: none !important;
  }
}
</style>